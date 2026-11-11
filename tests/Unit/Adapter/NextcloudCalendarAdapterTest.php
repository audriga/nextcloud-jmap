<?php

namespace OCA\JMAP\Tests\Unit\Adapter;

use OpenXPort\Adapter\NextcloudCalendarAdapter;
use PHPUnit\Framework\TestCase;

class NextcloudCalendarAdapterTest extends TestCase
{
    public function testGetSortOrderReturnsMappedValue(): void
    {
        $adapter = new NextcloudCalendarAdapter();
        $adapter->setCalendar([
            'id' => 'c1',
            '{http://apple.com/ns/ical/}calendar-order' => '5',
        ]);

        $this->assertSame(5, $adapter->getSortOrder());
    }

    public function testGetSortOrderReturnsNullWhenUnset(): void
    {
        $adapter = new NextcloudCalendarAdapter();
        $adapter->setCalendar(['id' => 'c1']);

        $this->assertNull($adapter->getSortOrder());
    }

    public function testGetIsVisibleReturnsMappedValue(): void
    {
        $adapter = new NextcloudCalendarAdapter();
        $adapter->setCalendar([
            'id' => 'c1',
            '{http://owncloud.org/ns}calendar-enabled' => '0',
        ]);

        $this->assertFalse($adapter->getIsVisible());
    }

    public function testGetIsVisibleReturnsNullWhenUnset(): void
    {
        $adapter = new NextcloudCalendarAdapter();
        $adapter->setCalendar(['id' => 'c1']);

        $this->assertNull($adapter->getIsVisible());
    }
}
