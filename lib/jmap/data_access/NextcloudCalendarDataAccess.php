<?php

namespace OpenXPort\DataAccess;

use OCA\DAV\CalDAV\CalDavBackend;
use OCP\IUserSession;

class NextcloudCalendarDataAccess extends AbstractDataAccess
{
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

    public function getAll($accountId = null)
    {
        $db = \OC::$server->getDatabaseConnection();

        $calendarsSql = 'SELECT * FROM `oc_calendars` WHERE `principaluri` = ?';
        $calendarsQueryParams = array($this->principalUri);
        $calendarsResult = $db->executeQuery($calendarsSql, $calendarsQueryParams);
        $calendars = $calendarsResult->fetchAll();

        return $calendars;
    }

    public function get($ids, $accountId = null)
    {
        if (is_null($ids) || empty($ids)) {
            $this->logger->warning("No IDs provided for get operation");
            return [];
        }

        $this->logger->info("Getting " . count($ids) . " calendars for user " . $this->principalUri);
        
        $result = [];
        
        foreach ($ids as $id) {
            $calendar = $this->backend->getCalendarById($id);
            
            if ($calendar && $calendar['principaluri'] === $this->principalUri) {
                $result[$id] = $calendar;
            } else {
                $this->logger->warning("Calendar not found or access denied: " . $id);
            }
        }

        return $result;
    }

    /**
     * Create calendars
     *
     * @param array calendarsToCreate Array of Id[calendarToCreate]
     *   Id is the creation ID that we send within a JMAP /set request
     *     for more info, see the "create" argument for JMAP /set requests here: https://jmap.io/spec-core.html#set
     *   calendarToCreate MUST have a 'uri' key (name of calendar) and can have two other keys:
     *   * {DAV:}displayname
     *   * {urn:ietf:params:xml:ns:caldav}calendar-description
     */
    public function create($calendarsToCreate, $accountId = null)
    {
        $calendarMap = [];

        if (is_null($calendarsToCreate)) {
            $this->logger->warning(
                "Calendar/set did not contain any data for creating for user " . $this->principalUri
            );
            return $calendarMap;
        }
        $this->logger->info("Creating " . count($calendarsToCreate) . " calendars for user " . $this->principalUri);


        foreach ($calendarsToCreate as $c) {
            // $calendarToCreate is an array of calendar properties
            $calendarToCreate = reset($c);
            $creationId = key($c);

            // In case $calendarToCreate is null or does not contain a name, we shouldn't perform writing, but instead
            // we should write false as the value for the corresponding $creationId key in $calendarMap
            if (
                is_null($calendarToCreate) ||
                !array_key_exists('uri', $calendarToCreate) ||
                strlen($calendarToCreate['uri'] == 0)
            ) {
                $calendarMap[$creationId] = false;
                continue;
            }

            // Use whatever string is stored under 'uri' as the displayname of the calendar. The URI is then created
            // by removing any invalid characters from the string.
            $name = $calendarToCreate['uri'];
            unset($calendarToCreate['uri']);

            if (!isset($calendarToCreate["{DAV:}displayname"])) {
                $calendarToCreate["{DAV:}displayname"] = $name;
            }

            $uri = $this->stripInvalidCharactersFromUri($name);

            $calendarMap[$creationId] =
                $this->backend->createCalendar($this->principalUri, $uri, $calendarToCreate);
        }

        return $calendarMap;
    }

    /**
     * Remove any characters from the URI that may lead to issues when creating a calendar.
     *
     * The list of allowed characters follows the main reccomendtaions found in the JMAP
     * // core id data type section: https://jmap.io/spec-core.html#the-id-data-type+
     *
     * @param string $uri The unfiltered name of the calendar as a string.
     *
     * @return string The stripped uri.
     */
    private function stripInvalidCharactersFromUri(string $uri)
    {
        // Remove leading and trailing whitespaces.
        $trimmedUri = trim($uri);

        // Remove leading and trailing slashes.
        $trimmedUri = preg_replace(array("/^\/+/", "/\/+$/"), "", $trimmedUri);

        // First, replace any slash with a hyphen. Two or more slash directly next to one another are replaced with a
        // single hyphen.
        // Afterwards, any character that is neither ASCII alpha-numerical (a-zA-Z0-9), a hyphen nor a underscore is
        // removed.
        $strippedUri = preg_replace(array("/\/+/", "/[^a-zA-z0-9-_]/"), array("-", ""), $trimmedUri);

        // Should the resulting uri be empty (""), we just return a hyphen. This can happen if the original uri only
        // contains whitespaces, slashes, non-ASCII characters, etc.
        return !empty($strippedUri) ? $strippedUri : "-";
    }

    public function destroy($ids, $accountId = null)
    {
        $calendarMap = [];
        if (is_null($ids)) {
            $this->logger->warning(
                "Calendar/set did not contain any data for destroying for user " . $this->principalUri
            );
            return $calendarMap;
        }
        $this->logger->info("Destroying " . sizeof($ids) . " calendars for user " . $this->principalUri);

        foreach ($ids as $id) {
            if (is_null($this->backend->getCalendarById($id))) {
                $calendarMap[$id] = 0;
                $this->logger->error("Calendar with the following ID does not exist: " . $id);
            } else {
                $this->backend->deleteCalendar($id, true);
                $calendarMap[$id] = 1;
            }
        }

        return $calendarMap;
    }

    public function query($accountId, $filter = null)
    {
        $db = \OC::$server->getDatabaseConnection();

        $calendarsSql = 'SELECT id FROM `oc_calendars` WHERE `principaluri` = ?';
        $calendarsQueryParams = array($this->principalUri);
        
        if (!is_null($filter)) {
            if (is_object($filter)) {
                $filter = json_decode(json_encode($filter), true);
            }
            
            if (is_array($filter)) {
                // Filter by name
                if (isset($filter['name'])) {
                    $calendarsSql .= ' AND `displayname` = ?';
                    array_push($calendarsQueryParams, $filter['name']);
                }
                
                // Filter by URI
                if (isset($filter['uri'])) {
                    $calendarsSql .= ' AND `uri` = ?';
                    array_push($calendarsQueryParams, $filter['uri']);
                }
                
                // Filter by color
                if (isset($filter['color'])) {
                    $calendarsSql .= ' AND `calendarcolor` = ?';
                    array_push($calendarsQueryParams, $filter['color']);
                }
            }
        }
        
        $calendarsResult = $db->executeQuery($calendarsSql, $calendarsQueryParams);
        $calendars = $calendarsResult->fetchAll();

        $ids = array_column($calendars, 'id');
        
        return $ids;
    }

    public function update($calendarsToUpdate, $accountId = null)
    {
        if (is_null($calendarsToUpdate)) {
            return [];
        }

        $this->logger->info("Updating " . count($calendarsToUpdate) . " calendars");
        $calendarMap = [];

        // Uses Sabre\DAV\PropPatch to update WebDAV properties following CalDAV standard.
        // Property names must include XML namespaces as required by RFC 4791 (CalDAV).
        $propertyMap = [
            'name' => '{DAV:}displayname',
            'color' => '{http://apple.com/ns/ical/}calendar-color',
            'description' => '{urn:ietf:params:xml:ns:caldav}calendar-description',
            'sortOrder' => '{http://calendarserver.org/ns/}calendar-order',
            'isVisible' => '{http://owncloud.org/ns}calendar-enabled',
            'timeZone' => '{urn:ietf:params:xml:ns:caldav}calendar-timezone'
        ];

        foreach ($calendarsToUpdate as $id => $data) {
            try {
                $calendar = $this->backend->getCalendarById($id);
                
                if (!$calendar || $calendar['principaluri'] !== $this->principalUri) {
                    $this->logger->error("Calendar not found or access denied: $id");
                    $calendarMap[$id] = false;
                    continue;
                }

                $mutations = [];
                
                foreach ($propertyMap as $jmapKey => $caldavKey) {
                    if (isset($data[$jmapKey])) {
                        $value = $data[$jmapKey];
                        if ($jmapKey === 'isVisible') {
                            $value = $value ? '1' : '0';
                        }
                        $mutations[$caldavKey] = $value;
                    }
                }

                if (!empty($mutations)) {
                    $propPatch = new \Sabre\DAV\PropPatch($mutations);
                    $this->backend->updateCalendar($id, $propPatch);
                    $propPatch->commit();
                    
                    $propPatchResult = $propPatch->getResult();
                    $allSucceeded = true;
                    foreach ($propPatchResult as $prop => $code) {
                        if ($code !== 200 && $code !== 204) {
                            $allSucceeded = false;
                        }
                    }
                    
                    $calendarMap[$id] = $allSucceeded;
                } else {
                    $calendarMap[$id] = false;
                }
            } catch (\Exception $e) {
                $this->logger->error("Failed to update calendar $id: " . $e->getMessage());
                $calendarMap[$id] = false;
            }
        }

        return $calendarMap;
    }

    /**
     * Get changes for calendars
     * 
     * Note: Nextcloud does not track calendar metadata changes in a separate table.
     * This only detects if the state changed, not which calendars were affected.
     * Returns empty arrays for created/updated/destroyed.
     */
    public function getChanges($sinceState, $maxChanges = 500, $accountId = null)
    {
        $currentState = $this->getCurrentState($accountId);
        
        return [
            'newState' => $currentState,
            'hasMoreChanges' => false,
            'created' => [],
            'updated' => [],
            'destroyed' => []
        ];
    }

    /**
     * Get current state for calendars
     * Returns the maximum synctoken from user's calendars
     */
    public function getCurrentState($accountId = null)
    {
        try {
            $db = \OC::$server->getDatabaseConnection();
            
            $query = "SELECT MAX(synctoken) as current_state 
                    FROM oc_calendars 
                    WHERE principaluri = ? 
                    AND uri != ?";
            
            $stmt = $db->prepare($query);
            $stmt->execute([$this->principalUri, \OCA\DAV\CalDAV\BirthdayService::BIRTHDAY_CALENDAR_URI]);
            $result = $stmt->fetch();
            
            return $result && $result['current_state'] ? (string)$result['current_state'] : "0";
            
        } catch (\Exception $e) {
            $this->logger->error("Failed to get current state: " . $e->getMessage());
            return "0";
        }
    }
}
