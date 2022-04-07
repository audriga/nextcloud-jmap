<?php

namespace OpenXPort\DataAccess;

class NextcloudCalendarEventDataAccess extends AbstractDataAccess
{
    public function getAll($accountId = null)
    {
        $db = \OC::$server->getDatabaseConnection();

        $userUid = $_SERVER['PHP_AUTH_USER'];

        $calendarsSql = 'SELECT id FROM `oc_calendars` WHERE `principaluri` = ?';
        $calendarsQueryParams = array('principals/users/' . $userUid);
        $calendarsResult = $db->executeQuery($calendarsSql, $calendarsQueryParams);
        $calendarIds = $calendarsResult->fetchAll();

        foreach ($calendarIds as $i => $calendarId) {
            $calendarIds[$i] = $calendarId['id'];
        }

        $calendarEventsSql = 'SELECT * FROM `oc_calendarobjects` WHERE `calendarid` IN (?) AND `componenttype` = ?';
        $calendarEventsQueryParams = array($calendarIds, 'VEVENT');
        $calendarEventsQueryTypes = array(\Doctrine\DBAL\Connection::PARAM_INT_ARRAY);
        $calendarEventsQuery = $db->executeQuery(
            $calendarEventsSql,
            $calendarEventsQueryParams,
            $calendarEventsQueryTypes
        );
        $calendarEvents = $calendarEventsQuery->fetchAll();

        $res = [];
        foreach ($calendarEvents as $calendarEvent) {
            $calendarId = $calendarEvent['calendarid'];
            $iCalendarData = $calendarEvent['calendardata'];
            $res[] = array($calendarId => $iCalendarData);
        }

        return $res;
    }

    public function get($ids, $accountId = null)
    {
        // TODO: Implement me
    }

    public function create($contactsToCreate, $accountId = null)
    {
        // TODO: Implement me
    }

    public function destroy($ids, $accountId = null)
    {
        // TODO: Implement me
    }

    public function query($accountId, $filter = null)
    {
        // TODO: Implement me
    }
}
