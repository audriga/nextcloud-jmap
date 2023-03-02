<?php

namespace OpenXPort\Adapter;

use OCA\DAV\CardDAV\Plugin as CardDAVPlugin;

class NextcloudAddressbookAdapter extends AbstractAdapter
{
    private $addressbook;

    /*
     * CardDavBackend expects an array of propeties instead of an actual AddressBook array for creating new a address
     * book.
     */
    public function getAddressbookAsProperties()
    {
        return array_filter([
            'uri' => $this->addressbook['uri'],
            // TODO '{DAV:}displayname' => $this->addressbook['uri'],
            '{' . CardDAVPlugin::NS_CARDDAV . '}addressbook-description' => $this->addressbook['description'] ?? null,
        ]);
    }

    public function setAddressbook($addressbook)
    {
        $this->addressbook = $addressbook;
    }

    public function getId()
    {
        return $this->addressbook['id'];
    }

    public function getName()
    {
        return $this->addressbook['uri'];
    }

    public function setName($name)
    {
        $this->addressbook['uri'] = $name;
    }

    public function getDescription()
    {
        return $this->addressbook['description'];
    }

    public function setDescription($desc)
    {
        $this->addressbook['description'] = $desc;
    }
}
