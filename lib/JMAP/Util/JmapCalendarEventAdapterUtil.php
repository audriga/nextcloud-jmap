<?php

namespace OCA\JMAP\JMAP\Util;

use OCA\JMAP\JMAP\CalendarEvent\NDay;

class JmapCalendarEventAdapterUtil
{
    // Below functions are for iCal -> JMAP value conversion

    public static function convertFromICalFreqToJmapFrequency($freq)
    {
        if (is_null($freq)) {
            return null;
        }

        $jmapFrequency = null;

        switch ($freq) {
            case 'YEARLY':
                $jmapFrequency = 'yearly';
                break;

            case 'MONTHLY':
                $jmapFrequency = 'monthly';
                break;

            case 'WEEKLY':
                $jmapFrequency = 'weekly';
                break;

            case 'DAILY':
                $jmapFrequency = 'daily';
                break;

            case 'HOURLY':
                $jmapFrequency = 'hourly';
                break;

            case 'MINUTELY':
                $jmapFrequency = 'minutely';
                break;

            case 'SECONDLY':
                $jmapFrequency = 'secondly';
                break;

            default:
                $jmapFrequency = null;
                break;
        }

        return $jmapFrequency;
    }

    public static function convertFromICalIntervalToJmapInterval($interval)
    {
        if (is_null($interval)) {
            // 1 is the default JMAP value for interval, that's why set to 1 if input is NULL
            return 1;
        }

        return $interval;
    }

    public static function convertFromICalRScaleToJmapRScale($rscale)
    {
        if (is_null($rscale)) {
            return null;
        }

        // JMAP rscale is essentially lowercase iCal rscale. That's why return lowercased version.
        return strtolower($rscale);
    }

    public static function convertFromICalSkipToJmapSkip($skip)
    {
        if (is_null($skip)) {
            return null;
        }

        $jmapSkip = null;

        switch ($skip) {
            case 'OMIT':
                $jmapSkip = 'omit';
                break;

            case 'BACKWARD':
                $jmapSkip = 'backward';
                break;

            case 'FORWARD':
                $jmapSkip = 'forward';
                break;

            default:
                $jmapSkip = null;
                break;
        }

        return $jmapSkip;
    }

    public static function convertFromICalWKSTToJmapFirstDayOfWeek($wkst)
    {
        if (is_null($wkst)) {
            return null;
        }

        $jmapFirstDayOfWeek = null;

        switch ($wkst) {
            case 'MO':
                $jmapFirstDayOfWeek = 'mo';
                break;

            case 'TU':
                $jmapFirstDayOfWeek = 'tu';
                break;

            case 'WE':
                $jmapFirstDayOfWeek = 'we';
                break;

            case 'TH':
                $jmapFirstDayOfWeek = 'th';
                break;

            case 'FR':
                $jmapFirstDayOfWeek = 'fr';
                break;

            case 'SA':
                $jmapFirstDayOfWeek = 'sa';
                break;

            case 'SU':
                $jmapFirstDayOfWeek = 'su';
                break;

            default:
                $jmapFirstDayOfWeek = null;
                break;
        }

        return $jmapFirstDayOfWeek;
    }

    public static function convertFromICalByDayToJmapByDay($byDay)
    {
        if (is_null($byDay)) {
            return null;
        }

        $splitByDayArray = explode(",", $byDay);

        $jmapByDay = [];

        foreach ($splitByDayArray as $bd) {
            // Parse the BYDAY string from iCal below

            $byDayWeekDayString;
            $byDayWeekNumberString;

            // Check if we have numeric characters and if yes, then separate them from the non-numeric accordingly
            if (!ctype_alpha($byDay)) {
                $splitByDay = str_split($byDay);
                $i = 0;
                while (is_numeric($splitByDay[$i])) {
                    $i++;
                }

                $byDayWeekNumberString = substr($byDay, 0, $i);
                $byDayWeekDayString = substr($byDay, $i);
            } else {
                $byDayWeekDayString = $byDay;
            }

            $jmapNDay = new NDay();
            $jmapNDay->setDay($byDayWeekDayString);
            if (!is_null($byDayWeekNumberString) && isset($byDayWeekNumberString)) {
                $jmapNDay->setNthOfPeriod((int) $byDayWeekNumberString);
            }

            array_push($jmapByDay, $jmapNDay);
        }

        return $jmapByDay;
    }

    public static function convertFromICalByMonthDayToJmapByMonthDay($byMonthDay)
    {
        if (is_null($byMonthDay)) {
            return null;
        }

        $splitByMonthDay = explode(",", $byMonthDay);

        foreach ($splitByMonthDay as $s) {
            $s = (int) $s;
        }

        return $splitByMonthDay;
    }

    public static function convertFromICalByMonthToJmapByMonth($byMonth)
    {
        if (is_null($byMonth)) {
            return null;
        }

        $splitByMonth = explode(",", $byMonth);

        return $splitByMonth;
    }

    public static function convertFromICalByYearDayToJmapByYearDay($byYearDay)
    {
        if (is_null($byYearDay)) {
            return null;
        }

        $splitByYearDay = explode(",", $byYearDay);

        foreach ($splitByYearDay as $s) {
            $s = (int) $s;
        }

        return $splitByYearDay;
    }

    public static function convertFromICalByWeekNoToJmapByWeekNo($byWeekNo)
    {
        if (is_null($byWeekNo)) {
            return null;
        }

        $splitByWeekNo = explode(",", $byWeekNo);

        foreach ($splitByWeekNo as $s) {
            $s = (int) $s;
        }

        return $splitByWeekNo;
    }

    public static function convertFromICalByHourToJmapByHour($byHour)
    {
        if (is_null($byHour)) {
            return null;
        }

        $splitByHour = explode(",", $byHour);

        foreach ($splitByHour as $s) {
            $s = (int) $s;
        }

        return $splitByHour;
    }

    public static function convertFromICalByMinuteToJmapByMinute($byMinute)
    {
        if (is_null($byMinute)) {
            return null;
        }

        $splitByMinute = explode(",", $byMinute);

        foreach ($splitByMinute as $s) {
            $s = (int) $s;
        }

        return $splitByMinute;
    }

    public static function convertFromICalBySecondToJmapBySecond($bySecond)
    {
        if (is_null($bySecond)) {
            return null;
        }

        $splitBySecond = explode(",", $bySecond);

        foreach ($splitBySecond as $s) {
            $s = (int) $s;
        }

        return $splitBySecond;
    }

    public static function convertFromICalBySetPositionToJmapBySetPos($bySetPosition)
    {
        if (is_null($bySetPosition)) {
            return null;
        }

        $splitBySetPosition = explode(",", $bySetPosition);

        foreach ($splitBySetPosition as $s) {
            $s = (int) $s;
        }

        return $splitBySetPosition;
    }

    public static function convertFromICalCountToJmapCount($count)
    {
        if (is_null($count)) {
            return null;
        }

        return (int) $count;
    }

    public static function convertFromICalUntilToJmapUntil($until)
    {
        if (is_null($until)) {
            return null;
        }

        $iCalUntilDate = \DateTime::createFromFormat("Ymd\THis", $until);
        $jmapUntil = date_format($iCalUntilDate, "Y-m-d\TH:i:s");

        return $jmapUntil;
    }
}
