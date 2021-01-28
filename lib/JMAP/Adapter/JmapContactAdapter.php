<?php

namespace OCA\JMAP\JMAP\Adapter;

use JeroenDesloovere\VCard\VCard;

use OCA\JMAP\JMAP\Contact\ContactInformation;
use OCA\JMAP\JMAP\Contact\Address;

class JmapContactAdapter {
    
    /** @var VCard */
    private $vCard;

    public function __construct($vCard) {
        $this->vCard = $vCard;
    }

    public function getVCard() {
        return $this->vCard;
    }

    public function getPrefix() {
        return $this->vCard->prefix;
    }

    public function getFirstName() {
        if (!isset($this->vCard->firstname) || is_null($this->vCard->firstname)) {
            $fullnameParts = explode(' ', $this->vCard->fullname);
            return $fullnameParts[0];
        }

        return $this->vCard->firstname;
    }

    public function getLastName() {
        if (!isset($this->vCard->lastname) || is_null($this->vCard->lastname)) {
            $fullnameParts = explode(' ', $this->vCard->fullname, 2);
            return $fullnameParts[1];
        }

        return $this->vCard->lastname;
    }

    public function getSuffix() {
        return $this->vCard->suffix;
    }

    public function getBirthday() {
        if (!is_null($this->vCard->birthday)) {
            return $this->vCard->birthday->format('Y-m-d');
        }

        return "0000-00-00";
    }

    public function getCompany() {
        return $this->vCard->organization;
    }

    public function getJobTitle() {
        return $this->vCard->title;
    }

    public function getEmails() {
        if (!isset($this->vCard->email) || is_null($this->vCard->email)) {
            return NULL;
        }

        $jmapEmails = [];
        
        if (isset($this->vCard->email['HOME']) && !empty($this->vCard->email['HOME'])) {
            foreach ($this->vCard->email['HOME'] as $homeEmail) {
                $jmapHomeEmail = new ContactInformation();
                $jmapHomeEmail->setType("home");
                $jmapHomeEmail->setValue($homeEmail);
                $jmapHomeEmail->setLabel(NULL);
                $jmapHomeEmail->setIsDefault(false);

                array_push($jmapEmails, $jmapHomeEmail);
            }
        } 
        
        if (isset($this->vCard->email['WORK']) && !empty($this->vCard->email['WORK'])) {
            foreach ($this->vCard->email['WORK'] as $workEmail) {
                $jmapWorkEmail = new ContactInformation();
                $jmapWorkEmail->setType("work");
                $jmapWorkEmail->setValue($workEmail);
                $jmapWorkEmail->setLabel(NULL);
                $jmapWorkEmail->setIsDefault(false);

                array_push($jmapEmails, $jmapWorkEmail);
            }
        } 
        
        if (isset($this->vCard->email['OTHER']) && !empty($this->vCard->email['OTHER'])) {
            foreach ($this->vCard->email['OTHER'] as $otherEmail) {
                $jmapOtherEmail = new ContactInformation();
                $jmapOtherEmail->setType("other");
                $jmapOtherEmail->setValue($otherEmail);
                $jmapOtherEmail->setLabel(NULL);
                $jmapOtherEmail->setIsDefault(false);

                array_push($jmapEmails, $jmapOtherEmail);
            }
        }

        return $jmapEmails;
    }

    public function getPhones() {
        if (!isset($this->vCard->phone) || is_null($this->vCard->phone) || empty($this->vCard->phone)) {
            return NULL;
        }

        $jmapPhones = [];
        
        if (isset($this->vCard->phone['"HOME,VOICE"']) && !empty($this->vCard->phone['"HOME,VOICE"'])) {
            foreach ($this->vCard->phone['"HOME,VOICE"'] as $homePhone) {
                $jmapHomePhone = new ContactInformation();
                $jmapHomePhone->setType("home");
                $jmapHomePhone->setValue($homePhone);
                $jmapHomePhone->setLabel(NULL);
                $jmapHomePhone->setIsDefault(false);

                array_push($jmapPhones, $jmapHomePhone);
            }
        } 
        
        if (isset($this->vCard->phone['"WORK,VOICE"']) && !empty($this->vCard->phone['"WORK,VOICE"'])) {
            foreach ($this->vCard->phone['"WORK,VOICE"'] as $workPhone) {
                $jmapWorkPhone = new ContactInformation();
                $jmapWorkPhone->setType("work");
                $jmapWorkPhone->setValue($workPhone);
                $jmapWorkPhone->setLabel(NULL);
                $jmapWorkPhone->setIsDefault(false);

                array_push($jmapPhones, $jmapWorkPhone);
            }
        }
        
        if (isset($this->vCard->phone['CELL']) && !empty($this->vCard->phone['CELL'])) {
            foreach ($this->vCard->phone['CELL'] as $cellPhone) {
                $jmapCellPhone = new ContactInformation();
                $jmapCellPhone->setType("mobile");
                $jmapCellPhone->setValue($cellPhone);
                $jmapCellPhone->setLabel(NULL);
                $jmapCellPhone->setIsDefault(false);

                array_push($jmapPhones, $jmapCellPhone);
            }
        } 
        
        if (isset($this->vCard->phone['"WORK,CELL"']) && !empty($this->vCard->phone['"WORK,CELL"'])) {
            foreach ($this->vCard->phone['"WORK,CELL"'] as $workCellPhone) {
                $jmapWorkCellPhone = new ContactInformation();
                $jmapWorkCellPhone->setType("work");
                $jmapWorkCellPhone->setValue($workCellPhone);
                $jmapWorkCellPhone->setLabel(NULL);
                $jmapWorkCellPhone->setIsDefault(false);

                array_push($jmapPhones, $jmapWorkCellPhone);
            }
        } 
        
        if (isset($this->vCard->phone['"HOME,CELL"']) && !empty($this->vCard->phone['"HOME,CELL"'])) {
            foreach ($this->vCard->phone['"HOME,CELL"'] as $homeCellPhone) {
                $jmapHomeCellPhone = new ContactInformation();
                $jmapHomeCellPhone->setType("home");
                $jmapHomeCellPhone->setValue($homeCellPhone);
                $jmapHomeCellPhone->setLabel(NULL);
                $jmapHomeCellPhone->setIsDefault(false);

                array_push($jmapPhones, $jmapHomeCellPhone);
            }
        } 
        
        if (isset($this->vCard->phone['FAX']) && !empty($this->vCard->phone['FAX'])) {
            foreach ($this->vCard->phone['FAX'] as $fax) {
                $jmapFax = new ContactInformation();
                $jmapFax->setType("fax");
                $jmapFax->setValue($fax);
                $jmapFax->setLabel(NULL);
                $jmapFax->setIsDefault(false);

                array_push($jmapPhones, $jmapFax);
            }
        } 
        
        if (isset($this->vCard->phone['"HOME,FAX"']) && !empty($this->vCard->phone['"HOME,FAX"'])) {
            foreach ($this->vCard->phone['"HOME,FAX"'] as $homeFax) {
                $jmapHomeFax = new ContactInformation();
                $jmapHomeFax->setType("home");
                $jmapHomeFax->setValue($homeFax);
                $jmapHomeFax->setLabel(NULL);
                $jmapHomeFax->setIsDefault(false);

                array_push($jmapPhones, $jmapHomeFax);
            }
        } 
        
        if (isset($this->vCard->phone['"WORK,FAX"']) && !empty($this->vCard->phone['"WORK,FAX"'])) {
            foreach ($this->vCard->phone['"WORK,FAX"'] as $workFax) {
                $jmapWorkFax = new ContactInformation();
                $jmapWorkFax->setType("work");
                $jmapWorkFax->setValue($workFax);
                $jmapWorkFax->setLabel(NULL);
                $jmapWorkFax->setIsDefault(false);

                array_push($jmapPhones, $jmapWorkFax);
            }
        } 
        
        if (isset($this->vCard->phone['PAGER']) && !empty($this->vCard->phone['PAGER'])) {
            foreach ($this->vCard->phone['PAGER'] as $pager) {
                $jmapPager = new ContactInformation();
                $jmapPager->setType("pager");
                $jmapPager->setValue($pager);
                $jmapPager->setLabel(NULL);
                $jmapPager->setIsDefault(false);

                array_push($jmapPhones, $jmapPager);
            }
        } 
        
        if (isset($this->vCard->phone['VOICE']) && !empty($this->vCard->phone['VOICE'])) {
            foreach ($this->vCard->phone['VOICE'] as $voicePhone) {
                $jmapVoicePhone = new ContactInformation();
                $jmapVoicePhone->setType("other");
                $jmapVoicePhone->setValue($voicePhone);
                $jmapVoicePhone->setLabel(NULL);
                $jmapVoicePhone->setIsDefault(false);

                array_push($jmapPhones, $jmapVoicePhone);
            }
        } 
        
        if (isset($this->vCard->phone['CAR']) && !empty($this->vCard->phone['CAR'])) {
            foreach ($this->vCard->phone['CAR'] as $carPhone) {
                $jmapCarPhone = new ContactInformation();
                $jmapCarPhone->setType("other");
                $jmapCarPhone->setValue($carPhone);
                $jmapCarPhone->setLabel(NULL);
                $jmapCarPhone->setIsDefault(false);

                array_push($jmapPhones, $jmapCarPhone);
            }
        } 
        
        if (isset($this->vCard->phone['"WORK,PAGER"']) && !empty($this->vCard->phone['"WORK,PAGER"'])) {
            foreach ($this->vCard->phone['"WORK,PAGER"'] as $workPager) {
                $jmapWorkPager = new ContactInformation();
                $jmapWorkPager->setType("work");
                $jmapWorkPager->setValue($workPager);
                $jmapWorkPager->setLabel(NULL);
                $jmapWorkPager->setIsDefault(false);

                array_push($jmapPhones, $jmapWorkPager);
            }
        }

        return $jmapPhones;
    }

    public function getOnline() {
        if (!isset($this->vCard->url) || is_null($this->vCard->url) || empty($this->vCard->url)) {
            return NULL;
        }

        $jmapOnline = [];

        if (isset($this->vCard->url['default']) && !empty($this->vCard->url['default'])) {
            foreach ($this->vCard->url['default'] as $website) {
                $jmapWebsite = new ContactInformation();
                $jmapWebsite->setType("uri");
                $jmapWebsite->setLabel(NULL);
                $jmapWebsite->setValue($website);
                $jmapWebsite->setIsDefault(false);

                array_push($jmapOnline, $jmapWebsite);
            }
        }

        return $jmapOnline;
    }

    public function getAddresses() {
        if (!isset($this->vCard->address) || is_null($this->vCard->address) || empty($this->vCard->address)) {
            return NULL;
        }

        $jmapAddresses = [];
        
        if (isset($this->vCard->address['HOME']) && !empty($this->vCard->address['HOME'])) {
            foreach ($this->vCard->address['HOME'] as $homeAddress) {
                $jmapHomeAddress = new Address();
                $jmapHomeAddress->setType("home");
                $jmapHomeAddress->setLabel($homeAddress->extended);
                $jmapHomeAddress->setStreet($homeAddress->street);
                $jmapHomeAddress->setLocality($homeAddress->city);
                $jmapHomeAddress->setRegion($homeAddress->region);
                $jmapHomeAddress->setPostcode($homeAddress->zip);
                $jmapHomeAddress->setCountry($homeAddress->country);
                $jmapHomeAddress->setIsDefault(false);

                array_push($jmapAddresses, $jmapHomeAddress);
            }
        }

        if (isset($this->vCard->address['WORK']) && !empty($this->vCard->address['WORK'])) {
            foreach ($this->vCard->address['WORK'] as $workAddress) {
                $jmapWorkAddress = new Address();
                $jmapWorkAddress->setType("work");
                $jmapWorkAddress->setLabel($workAddress->extended);
                $jmapWorkAddress->setStreet($workAddress->street);
                $jmapWorkAddress->setLocality($workAddress->city);
                $jmapWorkAddress->setRegion($workAddress->region);
                $jmapWorkAddress->setPostcode($workAddress->zip);
                $jmapWorkAddress->setCountry($workAddress->country);
                $jmapWorkAddress->setIsDefault(false);

                array_push($jmapAddresses, $jmapWorkAddress);
            }
        }

        if (isset($this->vCard->address['OTHER']) && !empty($this->vCard->address['OTHER'])) {
            foreach ($this->vCard->address['OTHER'] as $otherAddress) {
                $jmapOtherAddress = new Address();
                $jmapOtherAddress->setType("other");
                $jmapOtherAddress->setLabel($otherAddress->extended);
                $jmapOtherAddress->setStreet($otherAddress->street);
                $jmapOtherAddress->setLocality($otherAddress->city);
                $jmapOtherAddress->setRegion($otherAddress->region);
                $jmapOtherAddress->setPostcode($otherAddress->zip);
                $jmapOtherAddress->setCountry($otherAddress->country);
                $jmapOtherAddress->setIsDefault(false);

                array_push($jmapAddresses, $jmapOtherAddress);
            }
        }

        return $jmapAddresses;
    }

    public function getNotes() {
        return $this->vCard->note;
    }
}
