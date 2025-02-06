<?php

namespace OpenXPort\DataAccess;

use OCA\DAV\CalDAV\CalDavBackend;
use OCA\DAV\CalDAV\BirthdayService;
use OCP\IUserSession;

class NextcloudCalendarEventDataAccess extends AbstractDataAccess
{
    private $userId;
    private $backend;
    private $logger;
    private $principalUri;

    public function __construct(CalDavBackend $backend, IUserSession $userSession)
    {
        $this->backend = $backend;
        $this->logger = \OpenXPort\Util\Logger::getInstance();

        $user = $userSession->getUser();
        if ($user !== null) {
            $this->principalUri = 'principals/users/' . $user->getUID();
        } else {
            $this->logger->warning(
                "Was unable to find user via session. Falling back to PHP Auth User instead."
            );
            $this->principalUri = 'principals/users/' . $_SERVER['PHP_AUTH_USER'];
        }
    }

    private function getCalendars()
    {
        $this->userId = $_SERVER['PHP_AUTH_USER'];

        $calendars = $this->backend->getUsersOwnCalendars('principals/users/' . $this->userId);

        if (is_null($calendars) || empty($calendars)) {
            $this->logger->warning("User has no calendars: " . $this->principalUri);
            return [];
        }

        // Remove the Birthday Calendar from the list.
        for ($i = 0; $i < count($calendars); $i++) {
            if ($calendars[$i]['uri'] === BirthdayService::BIRTHDAY_CALENDAR_URI) {
                array_splice($calendars, $i, 1);
            }
        }

        return $calendars;
    }

    public function getAll($accountId = null)
    {
        $this->logger->info("Getting calendars");
        $calendars = $this->getCalendars();

        $calendarIds = [];

        foreach ($calendars as $i => $calendar) {
            $calendarIds[$i] = $calendar["id"];
        }

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

            $res[$id] = [
                "iCalendar" => $calendarEvent['calendardata'],
                "oxpProperties" => [
                    "calendarId" => $calendarId
                ]
            ];
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

            // Check if the user has any calendar and optionally create a new default one.
            // By default, the Birthday Calendar is excluded.
            if ($this->backend->getCalendarsForUserCount($this->principalUri) === 0) {
                $this->logger->notice("User has no Calendar. Creating new default calendar.");
                $this->createNewDefaultCalendar();
            }

            $calendars = $this->getCalendars();

            if (empty($calendars)) {
                throw new \Exception("Error: User has no calendars.");
            }

            $calendarId = null;

            if (
                !array_key_exists("oxpProperties", $eventToCreate) ||
                !array_key_exists("calendarId", $eventToCreate["oxpProperties"]) ||
                empty($eventToCreate["oxpProperties"]["calendarId"])
            ) {
                $this->logger->warning("No calendarId was given. Using the default calendar instead.");
                $defaultCalendarId = null;

                foreach ($calendars as $cal) {
                    if ($cal["uri"] == CalDavBackend::PERSONAL_CALENDAR_URI) {
                        $defaultCalendarId = $cal["id"];
                    }
                }

                if (is_null($defaultCalendarId)) {
                    $this->logger->warning("No default calendar found. Falling back to the first one in the list.");
                    $calendarId = $calendars[0]["id"];
                } else {
                    $calendarId = $defaultCalendarId;
                }
            } else {
                $calendarId = $eventToCreate["oxpProperties"]["calendarId"];
            }

            // Create a URI for each event for it to be added to the server.
            // This may create duplicate URIs
            $uri = md5($eventToCreate["iCalendar"]) . ".ics";

            $this->backend->createCalendarObject($calendarId, $uri, $eventToCreate["iCalendar"]);

            $eventMap[$creationId] = "$calendarId#$uri";
        }

        return $eventMap;
    }

    private function createNewDefaultCalendar()
    {
        try {
            $this->backend->createCalendar($this->principalUri, CalDavBackend::PERSONAL_CALENDAR_URI, [
                '{DAV:}displayname' => CalDavBackend::PERSONAL_CALENDAR_NAME,
                '{http://apple.com/ns/ical/}calendar-color' => "#0082c9",
                'components' => 'VEVENT'
            ]);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
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

            // Make sure the event exists.
            if (is_null($this->backend->getCalendarObject($calendarId, $uri))) {
                $eventMap[$id] = 0;
                $this->logger->error("Event with the following ID does not exist: " . $id);
            } else {
                $eventMap[$id] = 1;

                // Use the default calendar type and permanently delete the event.
                // see: https://github.com/nextcloud/server/blob/master/apps/dav/lib/CalDAV/CalDavBackend.php#L1417
                $this->backend->deleteCalendarObject($calendarId, $uri, 0, true);
            }
        }

        return $eventMap;
    }

    public function query($accountId, $filter = null)
    {
        // TODO: Implement me
    }
}
