<?php

namespace OpenXPort\DataAccess;

use OCA\DAV\CalDAV\CalDavBackend;

class NextcloudCalendarDataAccess extends AbstractDataAccess
{
    private $backend;
    private $logger;
    private $principalUri;

    public function __construct(CalDavBackend $backend)
    {
        $this->backend = $backend;
        $this->logger = \OpenXPort\Util\Logger::getInstance();
        $this->principalUri = 'principals/users/' . $_SERVER['PHP_AUTH_USER'];
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
        // TODO: Implement me
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
            } else {
                $name = $calendarToCreate['uri'];
                unset($calendarToCreate['uri']);
                $calendarMap[$creationId] =
                    $this->backend->createCalendar($this->principalUri, $name, $calendarToCreate);
            }
        }

        return $calendarMap;
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
        // TODO: Implement me
    }
}
