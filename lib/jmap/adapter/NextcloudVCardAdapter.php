<?php

namespace OpenXPort\Adapter;

use OpenXPort\Util\AdapterUtil;
use OpenXPort\Jmap\Contact\ContactInformation;

class NextcloudVCardAdapter extends VCardOxpAdapter
{
    // Reading IM data from Nextcloud requires special handling that's different from the logic
    // implemented in the generic VCardAdapter.
    // That's because we also have the Nextcloud-custom vCard property, called X-SOCIALPROFILE
    // which also contains IM data, such as social media usernames.
    // That's why we have to add the logic for this here as well.
    // We don't change the writing logic for IM, however, since writing all IM data from JMAP to
    // vCard that contains usernames (including data that is in X-SOCIALPROFILE) could be done in
    // the vCard property IMPP and does not need to necessarily be done in X-SOCIALPROFILE.
    // Due to the above-described reason, there's not setIm() method below this getIm() method
    public function getIm()
    {
        $jmapIms = [];

        $vCardIm = $this->vcard->IMPP;
        // Nextcloud has its custom vCard property, called X-SOCIALPROFILE
        // which contains social media account information, such as Facebook
        // or GitHub account, for instance.
        $vCardSocialProfile = $this->vcard->__get("X-SOCIALPROFILE");

        // Get all IM data and convert it to JMAP IM data
        if (AdapterUtil::isSetAndNotNull($vCardIm) && !empty($vCardIm)) {
            foreach ($vCardIm as $im) {
                $jmapIm = new ContactInformation();
                $jmapIm->setType('username');
                $jmapIm->setValue($im->getParts()[0]);
                $jmapIm->setLabel(null);
                $jmapIm->setIsDefault(false);

                array_push($jmapIms, $jmapIm);
            }
        }

        if (AdapterUtil::isSetAndNotNull($vCardSocialProfile) && !empty($vCardSocialProfile)) {
            foreach ($vCardSocialProfile as $socialProfile) {
                // Obtain the social profile's type from vCard (if there's any)
                // and set its lowercase value as the JMAP IM's label
                // See https://jmap.io/spec-contacts.html#contacts (property "online" with type "username")
                // TODO: The only thing which we're not doing completely as the JMAP spec here is that we're
                // capitalizing properly names, such as Facebook or GitHub, for example, but we're rather using
                // their lowercase versions (i.e., facebook or github)
                $socialProfileType = $socialProfile->parameters()['TYPE'];
                $jmapImLabel = (AdapterUtil::isSetAndNotNull($socialProfileType) && !empty($socialProfileType))
                    ? strtolower($socialProfileType) : null;

                $jmapIm = new ContactInformation();
                $jmapIm->setType('username');
                $jmapIm->setValue($socialProfile->getParts()[0]);
                $jmapIm->setLabel($jmapImLabel);
                $jmapIm->setIsDefault(false);

                array_push($jmapIms, $jmapIm);
            }
        }

        // In case we don't have any JMAP IM entries, return null
        if (count($jmapIms) === 0) {
            return null;
        }

        return $jmapIms;
    }
}
