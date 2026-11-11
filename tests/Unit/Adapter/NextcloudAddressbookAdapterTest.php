<?php

namespace OCA\JMAP\Tests\Unit\Adapter;

use OpenXPort\Adapter\NextcloudAddressbookAdapter;
use PHPUnit\Framework\TestCase;

class NextcloudAddressbookAdapterTest extends TestCase
{
    public function testGetUriReturnsMappedValue(): void
    {
        $adapter = new NextcloudAddressbookAdapter();
        $adapter->setAddressbook([
            'id' => '1',
            'uri' => 'contacts',
        ]);

        $this->assertSame('contacts', $adapter->getUri());
    }
}
