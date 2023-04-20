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
        return $this->calendar["uri"];
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
        return $this->calendar["color"];
    }

    public function setColor($color)
    {
        $this->calendar["color"] = $color;
    }
}
