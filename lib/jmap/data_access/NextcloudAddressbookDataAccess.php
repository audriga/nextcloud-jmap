<?php

namespace OpenXPort\DataAccess;

use OCA\DAV\CardDAV\CardDavBackend;
use OCP\IUserSession;

class NextcloudAddressbookDataAccess extends AbstractDataAccess
{
    private $backend;
    private $logger;
    private $principalUri;

    public function __construct(CardDavBackend $backend, IUserSession $userSession)
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

        $addressbooksSql = 'SELECT * FROM `oc_addressbooks` WHERE `principaluri` = ?';
        $addressbooksQueryParams = array($this->principalUri);
        $addressbooksResult = $db->executeQuery($addressbooksSql, $addressbooksQueryParams);
        $addressbooks = $addressbooksResult->fetchAll();

        return $addressbooks;
    }

    public function get($ids, $accountId = null)
    {
        if (is_null($ids) || empty($ids)) {
            $this->logger->warning("No IDs provided for get operation");
            return [];
        }

        $this->logger->info("Getting " . count($ids) . " address books for user " . $this->principalUri);
        
        $result = [];
        
        foreach ($ids as $id) {
            $addressbook = $this->backend->getAddressBookById($id);
            
            if ($addressbook && $addressbook['principaluri'] === $this->principalUri) {
                $result[$id] = $addressbook;
            } else {
                $this->logger->warning("Address book not found or access denied: " . $id);
            }
        }

        return $result;
    }

    /**
     * Create address books
     *
     * @param array booksToCreate Array of Id[bookToCreate]
     *   Id is the creation ID that we send within a JMAP /set request
     *     for more info, see the "create" argument for JMAP /set requests here: https://jmap.io/spec-core.html#set
     *   bookToCreate MUST have a 'uri' key (name of address book) and can have two other keys:
     *   * {DAV:}displayname
     *   * {urn:ietf:params:xml:ns:carddav}addressbook-description
     */
    public function create($booksToCreate, $accountId = null)
    {
        $bookMap = [];

        if (is_null($booksToCreate)) {
            $this->logger->warning(
                "AddressBook/set did not contain any data for creating for user " . $this->principalUri
            );
            return $bookMap;
        }
        $this->logger->info("Creating " . count($booksToCreate) . " address books for user " . $this->principalUri);


        foreach ($booksToCreate as $c) {
            // $bookToCreate is an array of address book properties
            $bookToCreate = reset($c);
            $creationId = key($c);

            // In case $bookToCreate is null or does not contain a name, we shouldn't perform writing, but instead we
            // should write false as the value for the corresponding $creationId key in $bookMap
            if (
                is_null($bookToCreate) ||
                !array_key_exists('uri', $bookToCreate) ||
                strlen($bookToCreate['uri'] == 0)
            ) {
                $bookMap[$creationId] = false;
            } else {
                $name = $bookToCreate['uri'];
                unset($bookToCreate['uri']);
                $bookMap[$creationId] = $this->backend->createAddressBook($this->principalUri, $name, $bookToCreate);
            }
        }

        return $bookMap;
    }

    public function destroy($ids, $accountId = null)
    {
        $bookMap = [];
        if (is_null($ids)) {
            $this->logger->warning(
                "AddressBook/set did not contain any data for destroying for user " . $this->principalUri
            );
            return $bookMap;
        }
        $this->logger->info("Destroying " . sizeof($ids) . " address books for user " . $this->principalUri);

        foreach ($ids as $id) {
            if (is_null($this->backend->getAddressBookById($id))) {
                $bookMap[$id] = 0;
                $this->logger->error("Address Book with the following ID does not exist: " . $id);
            } else {
                $this->backend->deleteAddressBook($id);
                $bookMap[$id] = 1;
            }
        }

        return $bookMap;
    }

    public function query($accountId, $filter = null)
    {
        $db = \OC::$server->getDatabaseConnection();

        $sql = 'SELECT id FROM `oc_addressbooks` WHERE `principaluri` = ?';
        $queryParams = array($this->principalUri);
        
        if (!is_null($filter)) {
            if (is_object($filter)) {
                $filter = json_decode(json_encode($filter), true);
            }
            
            if (is_array($filter)) {
                // Filter by name (displayname in database)
                if (isset($filter['name'])) {
                    $sql .= ' AND `displayname` = ?';
                    array_push($queryParams, $filter['name']);
                }
                
                // Filter by URI
                if (isset($filter['uri'])) {
                    $sql .= ' AND `uri` = ?';
                    array_push($queryParams, $filter['uri']);
                }
            }
        }
        
        $result = $db->executeQuery($sql, $queryParams);
        $addressbooks = $result->fetchAll();

        $ids = array_column($addressbooks, 'id');
        
        return $ids;
    }

    public function update($addressbooksToUpdate, $accountId = null)
    {
        if (is_null($addressbooksToUpdate)) {
            return [];
        }

        $this->logger->info("Updating " . count($addressbooksToUpdate) . " address books");
        $addressbookMap = [];
        // Map JMAP property names to WebDAV/CardDAV property names with XML namespaces
        // as defined in RFC 6352 (CardDAV)
        $propertyMap = [
            'name' => '{DAV:}displayname',
            'description' => '{urn:ietf:params:xml:ns:carddav}addressbook-description'
        ];

        foreach ($addressbooksToUpdate as $id => $data) {
            try {
                $addressbook = $this->backend->getAddressBookById($id);
                
                if (!$addressbook || $addressbook['principaluri'] !== $this->principalUri) {
                    $this->logger->error("Address book not found or access denied: $id");
                    $addressbookMap[$id] = false;
                    continue;
                }
                if (is_object($data)) {
                    $data = json_decode(json_encode($data), true);
                }

                $mutations = [];
                
                foreach ($propertyMap as $jmapKey => $caldavKey) {
                    if (isset($data[$jmapKey])) {
                        $mutations[$caldavKey] = $data[$jmapKey];
                    }
                }

                if (!empty($mutations)) {
                    $propPatch = new \Sabre\DAV\PropPatch($mutations);
                    $this->backend->updateAddressBook($id, $propPatch);
                    $propPatch->commit();
                    
                    $propPatchResult = $propPatch->getResult();
                    $allSucceeded = true;
                    foreach ($propPatchResult as $prop => $code) {
                        if ($code !== 200 && $code !== 204) {
                            $allSucceeded = false;
                        }
                    }
                    
                    $addressbookMap[$id] = $allSucceeded;
                } else {
                    $addressbookMap[$id] = false;
                }
            } catch (\Exception $e) {
                $this->logger->error("Failed to update address book $id: " . $e->getMessage());
                $addressbookMap[$id] = false;
            }
        }

        return $addressbookMap;
    }

    /**
     * Get changes for address books
     * 
     * Note: Nextcloud does not track address book metadata changes in a separate table.
     * This only detects if the state changed, not which address books were affected.
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
     * Get current state for address books
     * Returns the maximum synctoken from user's address books
     */
    public function getCurrentState($accountId = null)
    {
        try {
            $db = \OC::$server->getDatabaseConnection();
            
            $query = "SELECT MAX(synctoken) as current_state 
                    FROM oc_addressbooks 
                    WHERE principaluri = ?";
            
            $stmt = $db->prepare($query);
            $stmt->execute([$this->principalUri]);
            $result = $stmt->fetch();
            
            return $result && $result['current_state'] ? (string)$result['current_state'] : "0";
            
        } catch (\Exception $e) {
            $this->logger->error("Failed to get current state: " . $e->getMessage());
            return "0";
        }
    }
}
