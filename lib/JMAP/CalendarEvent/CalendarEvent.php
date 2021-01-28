<?php

namespace OCA\JMAP\JMAP\CalendarEvent;

use JsonSerializable;

class CalendarEvent implements JsonSerializable
{
    private $calendarId;
    private $start;
    private $duration;
    private $status;
    private $type;
    private $uid;
    private $prodId;
    private $created;
    private $updated;
    private $sequence;
    private $title;
    private $description;
    private $locations;
    private $keywords;
    private $recurrenceRule;
    private $recurrenceOverrides;
    private $priority;
    private $privacy;
    private $timeZone;


    public function getCalendarId()
    {
        return $this->calendarId;
    }

    public function setCalendarId($calendarId)
    {
        $this->calendarId = $calendarId;
    }

    public function getStart()
    {
        return $this->start;
    }

    public function setStart($start)
    {
        $this->start = $start;
    }

    public function getDuration()
    {
        return $this->duration;
    }

    public function setDuration($duration)
    {
        $this->duration = $duration;
    }

    public function getStatus() {
        return $this->status;
    }

    public function setStatus($status) {
        $this->status = $status;
    }

    public function getType() {
        return $this->type;
    }

    public function setType($type) {
        $this->type = $type;
    }

    public function getUid() {
        return $this->uid;
    }

    public function setUid($uid) {
        $this->uid = $uid;
    }

    public function getProdId() {
        return $this->prodId;
    }

    public function setProdId($prodId) {
        $this->prodId = $prodId;
    }

    public function getCreated() {
        return $this->created;
    }

    public function setCreated($created) {
        $this->created = $created;
    }

    public function getUpdated() {
        return $this->updated;
    }

    public function setUpdated($updated) {
        $this->updated = $updated;
    }

    public function getSequence() {
        return $this->sequence;
    }

    public function setSequence($sequence) {
        $this->sequence = $sequence;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function setDescription($description)
    {
        $this->description = $description;
    }

    public function getLocations() {
        return $this->locations;
    }

    public function setLocations($locations) {
        $this->locations = $locations;
    }

    public function getKeywords() {
        return $this->keywords;
    }

    public function setKeywords($keywords) {
        $this->keywords = $keywords;
    }

    public function getRecurrenceRule() {
        return $this->recurrenceRule;
    }

    public function setRecurrenceRule($recurrenceRule) {
        $this->recurrenceRule = $recurrenceRule;
    }

    public function getRecurrenceOverrides() {
        return $this->recurrenceOverrides;
    }

    public function setRecurrenceOverrides($recurrenceOverrides) {
        $this->recurrenceOverrides = $recurrenceOverrides;
    }

    public function getPriority() {
        return $this->priority;
    }

    public function setPriority($priority) {
        $this->priority = $priority;
    }

    public function getPrivacy() {
        return $this->privacy;
    }

    public function setPrivacy($privacy) {
        $this->privacy = $privacy;
    }

    public function getTimeZone() {
        return $this->timeZone;
    }

    public function setTimeZone($timeZone) {
        $this->timeZone = $timeZone;
    }

    public function jsonSerialize()
    {
        return (object)[
            "calendarId" => $this->getCalendarId(),
            "start" => $this->getStart(),
            "duration" => $this->getDuration(),
            "status" => $this->getStatus(),
            "@type" => $this->getType(),
            "uid" => $this->getUid(),
            "prodId" => $this->getProdId(),
            "created" => $this->getCreated(),
            "updated" => $this->getUpdated(),
            "sequence" => $this->getSequence(),
            "title" => $this->getTitle(),
            "description" => $this->getDescription(),
            "locations" => $this->getLocations(),
            "keywords" => $this->getKeywords(),
            "recurrenceRule" => $this->getRecurrenceRule(),
            "recurrenceOverrides" => $this->getRecurrenceOverrides(),
            "priority" => $this->getPriority(),
            "privacy" => $this->getPrivacy(),
            "timeZone" => $this->getTimeZone()
        ];
    }
}
