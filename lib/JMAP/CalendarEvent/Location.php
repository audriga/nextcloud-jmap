<?php

namespace OCA\JMAP\JMAP\CalendarEvent;

use JsonSerializable;

class Location implements JsonSerializable
{
    private $type;
    private $name;
    private $description;
    private $locationTypes;
    private $relativeTo;
    private $timeZone;
    private $coordinates;
    private $linkIds;

    public function getType()
    {
        return $this->type;
    }

    public function setType($type)
    {
        $this->type = $type;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function setDescription($description)
    {
        $this->description = $description;
    }

    public function getLocationTypes()
    {
        return $this->locationTypes;
    }

    public function setLocationTypes($locationTypes)
    {
        $this->locationTypes = $locationTypes;
    }

    public function getRelativeTo()
    {
        return $this->relativeTo;
    }

    public function setRelativeTo($relativeTo)
    {
        $this->relativeTo = $relativeTo;
    }

    public function getTimeZone()
    {
        return $this->timeZone;
    }

    public function setTimeZone($timeZone)
    {
        $this->timeZone = $timeZone;
    }

    public function getCoordinates()
    {
        return $this->coordinates;
    }

    public function setCoordinates($coordinates)
    {
        $this->coordinates = $coordinates;
    }

    public function getLinkIds()
    {
        return $this->linkIds;
    }

    public function setLinkIds($linkIds)
    {
        $this->linkIds = $linkIds;
    }

    public function jsonSerialize()
    {
        return (object)[
            "@type" => $this->getType(),
            "name" => $this->getName(),
            "description" => $this->getDescription(),
            "locationTypes" => $this->getLocationTypes(),
            "relativeTo" => $this->getRelativeTo(),
            "timeZone" => $this->getTimeZone(),
            "coordinates" => $this->getCoordinates(),
            "linkIds" => $this->getLinkIds()
        ];
    }
}
