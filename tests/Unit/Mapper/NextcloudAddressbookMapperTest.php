<?php

namespace OCA\JMAP\Tests\Unit\Mapper;

use OpenXPort\Adapter\NextcloudAddressbookAdapter;
use OpenXPort\Mapper\NextcloudAddressbookMapper;
use PHPUnit\Framework\TestCase;

class NextcloudAddressbookMapperTest extends TestCase
{
    public function testMapToJmapFlagsThePersonalAddressBookAsDefault(): void
    {
        $mapper = new NextcloudAddressbookMapper();
        $adapter = new NextcloudAddressbookAdapter();

        $result = $mapper->mapToJmap([
            ['id' => '1', 'uri' => 'contacts', '{DAV:}displayname' => 'Contacts'],
        ], $adapter);

        $this->assertTrue($result[0]->getIsDefault());
    }

    public function testMapToJmapDoesNotFlagOtherAddressBooksAsDefault(): void
    {
        $mapper = new NextcloudAddressbookMapper();
        $adapter = new NextcloudAddressbookAdapter();

        $result = $mapper->mapToJmap([
            ['id' => '2', 'uri' => 'shared-book', '{DAV:}displayname' => 'Shared Book'],
        ], $adapter);

        $this->assertFalse($result[0]->getIsDefault());
    }
}
