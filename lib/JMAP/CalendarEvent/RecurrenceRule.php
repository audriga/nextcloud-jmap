<?php

namespace OCA\JMAP\JMAP\CalendarEvent;

use JsonSerializable;

class RecurrenceRule implements JsonSerializable
{
    private $type;
    private $frequency;
    private $interval;
    private $rscale;
    private $skip;
    private $firstDayOfWeek;
    private $byDay;
    private $byMonthDay;
    private $byMonth;
    private $byYearDay;
    private $byWeekNo;
    private $byHour;
    private $byMinute;
    private $bySecond;
    private $bySetPosition;
    private $count;
    private $until;

    public function getType() {
        return $this->type;
    }

    public function setType($type) {
        $this->type = $type;
    }

    public function getFrequency() {
        return $this->frequency;
    }

    public function setFrequency($frequency) {
        $this->frequency = $frequency;
    }

    public function getInterval() {
        return $this->interval;
    }

    public function setInterval($interval) {
        $this->interval = $interval;
    }

    public function getRscale() {
        return $this->rscale;
    }

    public function setRscale($rscale) {
        $this->rscale = $rscale;
    }

    public function getSkip() {
        return $this->skip;
    }

    public function setSkip($skip) {
        $this->skip = $skip;
    }

    public function getFirstDayOfWeek() {
        return $this->firstDayOfWeek;
    }

    public function setFirstDayOfWeek($firstDayOfWeek) {
        $this->firstDayOfWeek = $firstDayOfWeek;
    }

    public function getByDay() {
        return $this->byDay;
    }

    public function setByDay($byDay) {
        $this->$byDay = $byDay;
    }

    public function getByMonthDay() {
        return $this->byMonthDay;
    }

    public function setByMonthDay($byMonthDay) {
        $this->byMonthDay = $byMonthDay;
    }

    public function getByMonth() {
        return $this->byMonth;
    }

    public function setByMonth($byMonth) {
        $this->byMonth = $byMonth;
    }

    public function getByYearDay() {
        return $this->byYearDay;
    }

    public function setByYearDay($byYearDay) {
        $this->byYearDay = $byYearDay;
    }

    public function getByWeekNo() {
        return $this->byWeekNo;
    }

    public function setByWeekNo($byWeekNo) {
        $this->byWeekNo = $byWeekNo;
    }

    public function getByHour() {
        return $this->byHour;
    }

    public function setByHour($byHour) {
        $this->byHour = $byHour;
    }

    public function getByMinute() {
        return $this->byMinute;
    }

    public function setByMinute($byMinute) {
        $this->byMinute = $byMinute;
    }

    public function getBySecond() {
        return $this->bySecond;
    }

    public function setBySecond($bySecond) {
        $this->bySecond = $bySecond;
    }

    public function getBySetPosition() {
        return $this->bySetPosition;
    }

    public function setBySetPosition($bySetPosition) {
        $this->bySetPosition = $bySetPosition;
    }

    public function getCount() {
        return $this->count;
    }

    public function setCount($count) {
        $this->count = $count;
    }

    public function getUntil() {
        return $this->until;
    }

    public function setUntil($until) {
        $this->until = $until;
    }

    public function jsonSerialize() {
        return (object)[
            "@type" => $this->getType(),
            "frequency" => $this->getFrequency(),
            "interval" => $this->getInterval(),
            "rscale" => $this->getRscale(),
            "skip" => $this->getSkip(),
            "firstDayOfWeek" => $this->getFirstDayOfWeek(),
            "byDay" => $this->getByDay(),
            "byMonthDay" => $this->getByMonthDay(),
            "byMonth" => $this->getByMonth(),
            "byYearDay" => $this->getByYearDay(),
            "byWeekNo" => $this->getByWeekNo(),
            "byHour" => $this->getByHour(),
            "byMinute" => $this->getByMinute(),
            "bySecond" => $this->getBySecond(),
            "bySetPosition" => $this->getBySetPosition(),
            "count" => $this->getCount(),
            "until" => $this->getUntil()
        ];
    }
} 
