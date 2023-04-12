<?php

namespace OpenXPort\DataAccess;

use OCA\DAV\CalDAV\CalDavBackend;

class NextcloudCalendarEventDataAccess extends AbstractDataAccess
{
    private $userId;
    private $backend;
    private $logger;
    private $principalUri;

    public function __construct(CalDavBackend $backend)
    {
        $this->backend = $backend;

        $this->logger = \OpenXPort\Util\Logger::getInstance();

        $this->principalUri = 'principals/users/' . $_SERVER['PHP_AUTH_USER'];
    }

    private function getCalendars()
    {
        $this->userId = $_SERVER['PHP_AUTH_USER'];

        $calendars = $this->backend->getUsersOwnCalendars('principals/users/' . $this->userId);

        $calendarIds = [];

        foreach ($calendars as $i => $calendar) {
            $calendarIds[$i] = $calendar['id'];
        }

        return $calendarIds;
    }

    public function getAll($accountId = null)
    {
        $this->logger->info("Getting calendars");
        $calendarIds = $this->getCalendars();

        $db = \OC::$server->getDatabaseConnection();



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
            $id = "$calendarId#$calendarEventUri";

            $res[$id] = $calendarEvent['calendardata'];
        }

        return $res;
    }

    public function get($ids, $accountId = null)
    {
        // TODO: Implement me
    }

    public function create($eventsToCreate, $accountId = null)
    {
        $eventMap = [];

        if (is_null($eventsToCreate)) {
            $this->logger->warning(
                "Calendar/set did not contain any data for creating for user " . $this->principalUri
            );
            return $eventMap;
        }
        $this->logger->info("Creating " . count($eventsToCreate) . " calendar events for user " . $this->principalUri);


        foreach ($eventsToCreate as $c) {
            $eventToCreate = reset($c);
            $creationId = key($c);

            if (is_null($eventToCreate)) {
                $eventMap[$creationId] = false;
                continue;
            }

            $calendarId = null;

            if (
                !array_key_exists("oxpProperties", $eventToCreate) ||
                !array_key_exists("calendarId", $eventToCreate["oxpProperties"]) ||
                empty($eventToCreate["oxpProperties"]["calendarId"])
            ) {
            }

            // Create a URI for each event for it to be added to the server.
            // This may create duplicate URIs
            $uri = md5(json_encode($eventToCreate)) . ".ics";

            $eventMap[$creationId] = $this->backend->createCalendarObject($creationId, $uri, $eventToCreate);
        }

        return $eventMap;
    }

    public function destroy($ids, $accountId = null)
    {
        $this->logger->info("Destroying " . sizeof($ids) . " events for user " . $this->principalUri);
        $eventMap = [];

        foreach ($ids as $id) {
            if (!mb_strpos($id, "#")) {
                $this->logger->error("Invalid ID. It does not contain '#': " . $id);
                $eventMap[$id] = 0;
                continue;
            }
            list($calendarId, $uri) = explode("#", $id);

            if (is_null($this->backend->getCalendarObjectById($this->principalUri, $calendarId))) {
                $eventMap[$id] = 0;
                $this->logger->error("Event with the following ID does not exist: " . $id);
            } else {
                $eventMap[$id] = 1;
                $this->backend->deleteCalendarObject($calendarId, $uri);
            }
        }

        return $eventMap;
    }

    public function query($accountId, $filter = null)
    {
        // TODO: Implement me
    }
}
