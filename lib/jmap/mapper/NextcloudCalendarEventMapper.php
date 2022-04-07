<?php

namespace OpenXPort\Mapper;

use OpenXPort\Jmap\Calendar\CalendarEvent;

class NextcloudCalendarEventMapper extends AbstractMapper
{
    public function mapFromJmap($jmapData, $adapter)
    {
        // TODO: Implement me
    }

    public function mapToJmap($data, $adapter)
    {
        $list = [];

        foreach ($data as $calendarEvent) {
            $calendarId = key($calendarEvent);
            $iCalendarData = $calendarEvent[$calendarId];

            $iCalObj = new \ZCiCal($iCalendarData);

            foreach ($iCalObj->tree->child as $node) {
                // Differentiate if the iCal component is an event and only then use it
                // for transformation to JMAP CalendarEvent (i.e. ignore VTODO, etc.)
                if ($node->getName() == "VEVENT") {
                    $adapter = new \OpenXPort\Adapter\NextcloudCalendarEventAdapter();
                    $adapter->setICalEvent($node);

                    $jmapCalendarEvent = new CalendarEvent();
                    $jmapCalendarEvent->setCalendarId($calendarId);
                    $jmapCalendarEvent->setStart($adapter->getDTStart());
                    $jmapCalendarEvent->setDuration($adapter->getDuration());
                    $jmapCalendarEvent->setStatus($adapter->getStatus());
                    $jmapCalendarEvent->setType("jsevent");
                    $jmapCalendarEvent->setUid($adapter->getUid());
                    $jmapCalendarEvent->setProdId($adapter->getProdId());
                    $jmapCalendarEvent->setCreated($adapter->getCreated());
                    $jmapCalendarEvent->setUpdated($adapter->getLastModified());
                    $jmapCalendarEvent->setSequence($adapter->getSequence());
                    $jmapCalendarEvent->setTitle($adapter->getSummary());
                    $jmapCalendarEvent->setDescription($adapter->getDescription());
                    $jmapCalendarEvent->setLocations($adapter->getLocation());
                    $jmapCalendarEvent->setKeywords($adapter->getCategories());
                    $jmapCalendarEvent->setRecurrenceRule($adapter->getRRule());
                    $jmapCalendarEvent->setRecurrenceOverrides($adapter->getExDate());
                    $jmapCalendarEvent->setPriority($adapter->getPriority());
                    $jmapCalendarEvent->setPrivacy($adapter->getClass());
                    $jmapCalendarEvent->setTimeZone($adapter->getTimeZone());

                    array_push($list, $jmapCalendarEvent);
                }
            }
        }

        return $list;
    }
}
