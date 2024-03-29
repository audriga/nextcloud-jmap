<?php

namespace OpenXPort\Mapper;

use OpenXPort\Jmap\Calendar\Calendar;

class NextcloudCalendarMapper extends AbstractMapper
{
    public function mapFromJmap($jmapData, $adapter)
    {
        $map = [];

        foreach ($jmapData as $id => $calendar) {
            $adapter->setCalendar([]);
            $adapter->setName($calendar->getName());
            $adapter->setDescription($calendar->getDescription());
            // TODO https://web.audriga.com/mantis/view.php?id=6289
            // $adapter->setColor($calendar->color);

            array_push($map, array($id => $adapter->getCalendarAsProperties()));
        }

        return $map;
    }

    public function mapToJmap($data, $adapter)
    {
        $list = [];

        foreach ($data as $calendar) {
            $adapter->setCalendar($calendar);

            $jmapCalendar = new Calendar();
            $jmapCalendar->setId($adapter->getId());
            $jmapCalendar->setName($adapter->getName());
            $jmapCalendar->setDescription($adapter->getDescription());
            // TODO https://web.audriga.com/mantis/view.php?id=6289
            // $jmapCalendar->setColor($adapter->getColor());

            array_push($list, $jmapCalendar);
        }

        return $list;
    }
}
