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
        $calendars = $this->backend->getUsersOwnCalendars($this->principalUri);

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
                    "calendarId" => (string)$calendarId
                ]
            ];
        }

        return $res;
    }

    public function get($ids, $accountId = null)
    {
        if (is_null($ids) || empty($ids)) {
            $this->logger->warning("No IDs provided for get operation");
            return [];
        }

        $this->logger->info("Getting " . count($ids) . " calendar events for user " . $this->principalUri);
        
        $res = [];
        $db = \OC::$server->getDatabaseConnection();

        foreach ($ids as $id) {
            // ID format: "calendarId#uri"
            if (!mb_strpos($id, "#")) {
                $this->logger->error("Invalid ID format. It does not contain '#': " . $id);
                continue;
            }

            list($calendarId, $uri) = explode("#", $id, 2);

            // Fetch the specific event from database
            $sql = 'SELECT * FROM `oc_calendarobjects` WHERE `calendarid` = ? AND `uri` = ? AND `componenttype` = ?';
            $queryParams = [$calendarId, $uri, 'VEVENT'];
            $query = $db->executeQuery($sql, $queryParams);
            $calendarEvent = $query->fetch();

            if ($calendarEvent) {
                $res[$id] = [
                    "iCalendar" => $calendarEvent['calendardata'],
                    "oxpProperties" => [
                        "calendarId" => (string)$calendarId
                    ]
                ];
            } else {
                $this->logger->warning("Event not found: " . $id);
            }
        }

        return $res;
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
        $this->logger->info("Querying calendar events");
        
        $calendars = $this->getCalendars();
        $calendarIds = array_column($calendars, 'id');
        
        if (empty($calendarIds)) {
            return [];
        }
        
        $db = \OC::$server->getDatabaseConnection();
        
        $sql = 'SELECT calendarid, uri FROM `oc_calendarobjects` WHERE `calendarid` IN (?) AND `componenttype` = ?';
        $queryParams = [$calendarIds, 'VEVENT'];
        $queryTypes = [\Doctrine\DBAL\Connection::PARAM_INT_ARRAY];
        
        if (!is_null($filter)) {
            if (is_object($filter)) {
                $filter = json_decode(json_encode($filter), true);
            }
            
            if (is_array($filter)) {
                // Filter by calendar ID
                if (isset($filter['calendarId'])) {
                    $sql = 'SELECT calendarid, uri FROM `oc_calendarobjects` WHERE `calendarid` = ? AND `componenttype` = ?';
                    $queryParams = [$filter['calendarId'], 'VEVENT'];
                    $queryTypes = [];
                }
                
                // Filter by UID
                if (isset($filter['uid'])) {
                    $sql .= ' AND `uid` = ?';
                    array_push($queryParams, $filter['uid']);
                }
                
                // Filter by created date (timestamp)
                if (isset($filter['createdAfter'])) {
                    $sql .= ' AND `created_at` >= ?';
                    array_push($queryParams, $filter['createdAfter']);
                }
                
                if (isset($filter['createdBefore'])) {
                    $sql .= ' AND `created_at` <= ?';
                    array_push($queryParams, $filter['createdBefore']);
                }
                
                // Filter by last modified date (timestamp)
                if (isset($filter['modifiedAfter'])) {
                    $sql .= ' AND `lastmodified` >= ?';
                    array_push($queryParams, $filter['modifiedAfter']);
                }
                
                if (isset($filter['modifiedBefore'])) {
                    $sql .= ' AND `lastmodified` <= ?';
                    array_push($queryParams, $filter['modifiedBefore']);
                }
                
                // Filter by classification
                if (isset($filter['classification'])) {
                    $sql .= ' AND `classification` = ?';
                    array_push($queryParams, $filter['classification']);
                }
            }
        }
        
        $query = $db->executeQuery($sql, $queryParams, $queryTypes);
        $events = $query->fetchAll();
        
        $ids = [];
        foreach ($events as $event) {
            $ids[] = $event['calendarid'] . '#' . $event['uri'];
        }
        
        return $ids;
    }

    public function update($eventsToUpdate, $accountId = null)
    {
        $eventMap = [];

        if (is_null($eventsToUpdate)) {
            $this->logger->warning(
                "CalendarEvent/set did not contain any data for updating for user " . $this->principalUri
            );
            return $eventMap;
        }

        $this->logger->info("Updating " . count($eventsToUpdate) . " calendar events for user " . $this->principalUri);

        foreach ($eventsToUpdate as $id => $eventData) {
            // ID format: "calendarId#uri"
            if (!mb_strpos($id, "#")) {
                $this->logger->error("Invalid ID format. It does not contain '#': " . $id);
                $eventMap[$id] = false;
                continue;
            }

            list($calendarId, $uri) = explode("#", $id, 2);

            // Check if event exists
            $existingEvent = $this->backend->getCalendarObject($calendarId, $uri);
            if (is_null($existingEvent)) {
                $this->logger->error("Event with ID does not exist: " . $id);
                $eventMap[$id] = false;
                continue;
            }

            if (is_null($eventData)) {
                $eventMap[$id] = false;
                continue;
            }

            try {
                // Update the calendar object
                $this->backend->updateCalendarObject($calendarId, $uri, $eventData["iCalendar"]);
                $eventMap[$id] = true;
            } catch (\Exception $e) {
                $this->logger->error("Failed to update event " . $id . ": " . $e->getMessage());
                $eventMap[$id] = false;
            }
        }

        return $eventMap;
    }

    /**
     * Get changes since a specific state using Nextcloud's calendar change tracking
     * 
     * Implements JMAP CalendarEvent/changes method as per RFC 8620 Section 5.2
     * Uses oc_calendarchanges table with operation codes: 1=created, 2=updated, 3=deleted
     */
    public function getChanges($sinceState, $maxChanges = 500, $accountId = null)
    {
        // Get user's calendars
        $calendars = $this->getCalendars();
        
        if (is_null($calendars) || empty($calendars)) {
            return [
                'newState' => $sinceState,
                'hasMoreChanges' => false,
                'created' => [],
                'updated' => [],
                'destroyed' => []
            ];
        }
        
        $calendarIds = array_column($calendars, 'id');
        
        // Query changes from oc_calendarchanges table
        $db = \OC::$server->getDatabaseConnection();
        $query = "SELECT uri, synctoken, calendarid, operation 
                FROM oc_calendarchanges 
                WHERE calendarid IN (?) 
                AND synctoken > ? 
                ORDER BY synctoken ASC 
                LIMIT ?";
        
        $result = $db->executeQuery(
            $query, 
            [$calendarIds, (int)$sinceState, $maxChanges + 1],
            [\Doctrine\DBAL\Connection::PARAM_INT_ARRAY,
            \Doctrine\DBAL\ParameterType::INTEGER,
            \Doctrine\DBAL\ParameterType::INTEGER]
        );
        $rows = $result->fetchAll();

        // Check if there are more changes than requested (pagination)
        $hasMoreChanges = count($rows) > $maxChanges;
        if ($hasMoreChanges) {
            array_pop($rows);
        }

        // Process change records
        $changes = ['created' => [], 'updated' => [], 'destroyed' => []];
        $newState = $sinceState;

        foreach ($rows as $row) {
            if (empty($row['uri'])) continue;
            
            $newState = (string)$row['synctoken'];
            $eventId = $row['calendarid'] . '#' . $row['uri'];
            
            // Map operation code to change type
            $operationMap = [1 => 'created', 2 => 'updated', 3 => 'destroyed'];
            if (isset($operationMap[$row['operation']])) {
                $changes[$operationMap[$row['operation']]][] = $eventId;
            }
        }

        return array_merge($changes, [
            'newState' => $newState,
            'hasMoreChanges' => $hasMoreChanges
        ]);
    }

    /**
     * Get current state (latest synctoken) for calendar events
     */
    public function getCurrentState($accountId = null)
    {
        try {
            $calendars = $this->getCalendars();
            
            if (is_null($calendars) || empty($calendars)) {
                return "0";
            }
            
            $calendarIds = array_column($calendars, 'id');
            $placeholders = implode(',', array_fill(0, count($calendarIds), '?'));
            
            $db = \OC::$server->getDatabaseConnection();
            $query = "SELECT MAX(synctoken) as current_state 
                    FROM oc_calendarchanges 
                    WHERE calendarid IN ($placeholders)";
            
            $stmt = $db->prepare($query);
            $stmt->execute($calendarIds);
            $result = $stmt->fetch();
            
            return $result && $result['current_state'] ? (string)$result['current_state'] : "0";
            
        } catch (\Exception $e) {
            $this->logger->error("Failed to get current state: " . $e->getMessage());
            return "0";
        }
    }
}
