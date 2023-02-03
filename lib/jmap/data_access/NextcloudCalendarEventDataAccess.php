<?php

namespace OpenXPort\DataAccess;

use OCA\DAV\CalDAV\CalDavBackend;

class NextcloudCalendarEventDataAccess extends AbstractDataAccess
{
    private $userId;
    private $backend;
    private $logger;

    public function __construct(CalDavBackend $backend)
    {
        $this->backend = $backend;

        $this->logger = \OpenXPort\Util\Logger::getInstance();
    }

    private function getCalendars()
    {
        $this->userUid = $_SERVER['PHP_AUTH_USER'];

        $calendars = $this->backend->getUsersOwnCalendars('principals/users/' . $this->userUid);

        $calendarIds = [];

        foreach ($calendars as $i => $calendar) {
            $calendarIds[$i] = $calendar['id'];
        }

        return $calendarIds;
    }

    public function getAll($accountId = null)
    {
        $this->logger->info("Getting contacts");
        $calendarIds = $this->getCalendars();

        $db = \OC::$server->getDatabaseConnection();


        /*
        $this->userUid = $_SERVER['PHP_AUTH_USER'];

        $calendarsSql = 'SELECT id FROM `oc_calendars` WHERE `principaluri` = ?';
        $calendarsQueryParams = array('principals/users/' . $this->userUid);
        $calendarsResult = $db->executeQuery($calendarsSql, $calendarsQueryParams);
        $calendarIds = $calendarsResult->fetchAll();

        foreach ($calendarIds as $i => $calendarId) {
            $calendarIds[$i] = $calendarId['id'];
        }
        */

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
            $calendarEventUri = $calendarEvent['uri'];
            $id = "$calendarID#$calendarEventUri";

            $res[$id] = $calendarEvent['calendardata'];

            /*
            $calendarId = $calendarEvent['calendarid'];
            $iCalendarData = $calendarEvent['calendardata'];
            $res[] = array($calendarId => $iCalendarData);
            */
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
