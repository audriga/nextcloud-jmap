<?php

namespace OpenXPort\Adapter;

use OCA\DAV\CalDAV\Plugin as CalDAVPlugin;

class NextcloudCalendarAdapter extends AbstractAdapter
{
    private $calendar;

    public function getCalendarAsProperties()
    {
        return array_filter([
            "uri" => $this->calendar["uri"],
            "{" . CalDAVPlugin::NS_CALDAV . "}calendar-description" => $this->calendar["description"] ?? null,
            "{http://apple.com/ns/ical/}calendar-color" => $this->calendar["{http://apple.com/ns/ical/}calendar-color"] ?? null,
        ]);
    }

    public function setCalendar($calendar)
    {
        $this->calendar = $calendar;
    }

    public function getId()
    {
        return $this->calendar["id"];
    }

    public function getName()
    {
        return $this->calendar["{DAV:}displayname"] ?? $this->calendar["uri"];
    }

    public function setName($name)
    {
        $this->calendar["uri"] = $name;
    }

    public function getDescription()
    {
        return $this->calendar["description"];
    }

    public function setDescription($description)
    {
        $this->calendar["description"] = $description;
    }

    public function getColor()
    {
        return $this->calendar["{http://apple.com/ns/ical/}calendar-color"] ?? null;
    }

    public function setColor($color)
    {
        $this->calendar["{http://apple.com/ns/ical/}calendar-color"] = $color;
    }

    public function getSortOrder()
    {
        $sortOrder = $this->calendar["{http://apple.com/ns/ical/}calendar-order"] ?? null;
        return $sortOrder === null ? null : (int)$sortOrder;
    }

    public function getIsVisible()
    {
        $isVisible = $this->calendar["{http://owncloud.org/ns}calendar-enabled"] ?? null;
        return $isVisible === null ? null : (bool)$isVisible;
    }
}
