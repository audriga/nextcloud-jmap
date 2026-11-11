<?php

namespace OpenXPort\Mapper;

use OCA\DAV\CardDAV\CardDavBackend;
use OpenXPort\Jmap\JSContact\AddressBook;

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
            $jmapAddressbook->setId((string)$adapter->getId());
            $jmapAddressbook->setName($adapter->getName());
            $jmapAddressbook->setDescription($adapter->getDescription());
            $jmapAddressbook->setIsDefault($adapter->getUri() === CardDavBackend::PERSONAL_ADDRESSBOOK_URI);

            array_push($list, $jmapAddressbook);
        }

        return $list;
    }
}
