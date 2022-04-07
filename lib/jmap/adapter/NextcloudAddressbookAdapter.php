<?php

namespace OpenXPort\Adapter;

class NextcloudAddressbookAdapter extends AbstractAdapter
{
    private $addressbook;

    public function getAddressbook()
    {
        return $this->addressbook;
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

    public function getDescription()
    {
        return $this->addressbook['description'];
    }
}
