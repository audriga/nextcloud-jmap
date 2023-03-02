<?php

namespace OpenXPort\Mapper;

use OpenXPort\Jmap\Contact\AddressBook;

class NextcloudAddressbookMapper extends AbstractMapper
{
    public function mapFromJmap($jmapData, $adapter)
    {
        $map = [];

        foreach ($jmapData as $id => $book) {
            $adapter->setAddressbook([]);
            $adapter->setName($book->name);
            $adapter->setDescription($book->description);

            array_push($map, array($id => $adapter->getAddressbookAsProperties()));
        }

        return $map;
    }

    public function mapToJmap($data, $adapter)
    {
        $list = [];

        foreach ($data as $addressbook) {
            $adapter->setAddressbook($addressbook);

            $jmapAddressbook = new AddressBook();
            $jmapAddressbook->setId($adapter->getId());
            $jmapAddressbook->setName($adapter->getName());
            $jmapAddressbook->setDescription($adapter->getDescription());

            array_push($list, $jmapAddressbook);
        }

        return $list;
    }
}
