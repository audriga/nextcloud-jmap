<?php

namespace OpenXPort\DataAccess;

use OCA\DAV\CardDAV\CardDavBackend;
use OCP\IUserSession;

class NextcloudContactDataAccess extends AbstractDataAccess
{
    private $principalUri;
    private $backend;
    private $logger;

    public function __construct(CardDavBackend $backend, IUserSession $userSession)
    {
        $this->backend = $backend;
        $this->logger = \OpenXPort\Util\Logger::getInstance();

        // In order to modify the user's contact data, we need the user's UID which luckily is the user's
        // Nextcloud username.
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

    private function getAddressBooks()
    {
        $addressBooks = $this->backend->getUsersOwnAddressBooks($this->principalUri);

        // Since we receive an array of arrays holding the addressbook IDs, we want to restructure
        // it such that we only have one array, containing all IDs. That's why we flatten the result
        // that we received in the foreach below.

        if (is_null($addressBooks)) {
            // TODO we might want to handle this in the future
            $this->logger->error("User has no address books " . $this->principalUri);
            return null;
        }

        return $addressBooks;
    }

    public function getAll($accountId = null)
    {
        $this->logger->info("Getting contacts");

        $addressBooks = $this->getAddressBooks();

        $addressBookIds = [];

        foreach ($addressBooks as $i => $addressBook) {
            $addressBookIds[$i] = $addressBook['id'];
        }

        // Obtain a database connection in order to be able to query the DB and read contact data from it
        $db = \OC::$server->getDatabaseConnection();

        // Currently commented out the reading of shared addressbooks for a given user below, since only own
        // addressbooks of a user should be read by default.
        // === START COMMENTED OUT SHARED ADDRESSBOOKS SECTION ===
        /*
        // Next, read all of the addressbooks that are shared with the user. For this we query all resourceids
        // from the DB table 'oc_dav_shares'.
        // We filter by type = addressbook, since we only want the shared addressbooks here
        // (and no shared calendars, for instance).
        // We also filter by principaluri = <username>, where <username> is the user's username that we already
        // have from above.
        $sharedAddressbooksSql = 'SELECT resourceid FROM `oc_dav_shares` WHERE `type` = ? AND `principaluri` = ?';
        $sharedAddressbooksQueryParams = array('addressbook', 'principals/users/' . $userUid);
        $sharedAddressbooksResult = $db->executeQuery($sharedAddressbooksSql, $sharedAddressbooksQueryParams);
        $sharedAddressbookIds = $sharedAddressbooksResult->fetchAll();
        // Similarly to the own addressbook IDs above, flatten the result array with the IDs of the shared
        // addressbooks that we received after executing the query.
        foreach ($sharedAddressbookIds as $i => $sharedAddressbookId) {
            $sharedAddressbookIds[$i] = $sharedAddressbookId['resourceid'];
        }

        // Merge the IDs of the user's own addressbooks together with the IDs of the addressboks that are
        // shared with the user. This is handy, since we'll need all addressbook IDs below in order to read
        // the actual contacts from the DB that are associated with these addressbooks.
        $addressBookIds = array_merge($addressBookIds, $sharedAddressbookIds);
         */
        // === END COMMENTED OUT SHARED ADDRESSBOOKS SECTION ===

        // Now we read all contacts from the DB table 'oc_cards'. Here we filter in the SQL query by addressbookid
        // and for this we supply all the addressbook IDs from above.
        $contactsSql = 'SELECT * FROM `oc_cards` WHERE `addressbookid` IN (?)';
        $contactsQueryParams = array($addressBookIds);
        // Since we're passing the addressbook IDs as a SQL query parameter here, we need to also specify that they're
        // an int array. This is needed for the prepared statement and done by supplying $contactsQueryTypes which
        // information about exactly this type (it comes from the Doctrine library which is used in Nextcloud as ORM).
        $contactsQueryTypes = array(\Doctrine\DBAL\Connection::PARAM_INT_ARRAY);
        $contactsQuery = $db->executeQuery(
            $contactsSql,
            $contactsQueryParams,
            $contactsQueryTypes
        );
        $contacts = $contactsQuery->fetchAll();

        // After obtaining all contacts in $contacts, we create an array $res which contains each contact's ID as a key
        // and the respective contact's vCard representation as a value.
        $res = [];
        foreach ($contacts as $contact) {
            // We use the etag cache key as IDs. Inspiration from
            //  https://github.com/nextcloud/server/blob/master/apps/dav/lib/CardDAV/CardDavBackend.php#L705
            $addressBookId = $contact['addressbookid'];
            $cardUri = $contact['uri'];
            $id = "$addressBookId#$cardUri";

            $res[$id] = [
                "vCard" => $contact['carddata'],
                "oxpProperties" => [
                    "addressBookId" => $addressBookId
                ]
            ];
        }

        return $res;
    }

    public function get($ids, $accountId = null)
    {
        if (empty($ids)) {
            return [];
        }

        $this->logger->info("Getting " . sizeof($ids) . " contacts by ID from database");
        
        $result = [];
        
        foreach ($ids as $id) {
            try {
                // ID format: "addressBookId#uri"
                if (!mb_strpos($id, "#")) {
                    $this->logger->error("Invalid ID format. It does not contain '#': " . $id);
                    continue;
                }

                list($addressBookId, $uri) = explode("#", $id, 2);

                $addressBook = $this->backend->getAddressBookById($addressBookId);
                if (!$addressBook || $addressBook['principaluri'] !== $this->principalUri) {
                    $this->logger->error("Access denied to address book: " . $addressBookId);
                    continue;
                }

                $card = $this->backend->getCard($addressBookId, $uri);
                
                if (!is_null($card) && !empty($card)) {
                    $result[$id] = [
                        'vCard' => $card['carddata'],
                        'uri' => $card['uri'],
                        'addressBookId' => $addressBookId
                    ];
                } else {
                    $this->logger->warning("Contact not found: " . $id);
                }
                
            } catch (\Exception $e) {
                $this->logger->error("Failed to get contact " . $id . ": " . $e->getMessage());
            }
        }
        
        return $result;
    }
    public function create($contactsToCreate, $accountId = null)
    {
        $this->logger->info("Creating " . sizeof($contactsToCreate) . " contacts for user " . $this->principalUri);

        $contactMap = [];

        foreach ($contactsToCreate as $c) {
            // $contactToCreate is a vCard that we receive
            $contactToCreate = reset($c);

            // $creationId is the creation ID that we send within a JMAP /set request
            // For more info, see the "create" argument for JMAP /set requests here: https://jmap.io/spec-core.html#set
            $creationId = key($c);

            // In case $contactToCreate is null, we shouldn't perform contact writing, but instead we should
            // write false as the value for the corresponding $creationId key in $contactMap
            if (is_null($contactToCreate)) {
                $contactMap[$creationId] = false;
                continue;
            }

            if ($this->backend->getAddressBooksForUserCount($this->principalUri) === 0) {
                $this->logger->notice("User has no Address Book. Creating new default Address Book.");
                $this->createNewDefaultBook();
            }

            // assume that the first address book is the one we want to create contacts in
            $addressBooks = $this->getAddressBooks();

            if (empty($addressBooks)) {
                throw new \Exception("User has no address books.");
            }

            $addressBookId = null;

            // Write into default address book in case no ID was given
            if (
                !array_key_exists('oxpProperties', $contactToCreate) ||
                !array_key_exists('addressBookId', $contactToCreate['oxpProperties']) ||
                empty($contactToCreate['oxpProperties']['addressBookId'])
            ) {
                $this->logger->warning("No addressBookId was set. Trying to write into default address book.");
                $defaultBookId = null;

                foreach ($addressBooks as $book) {
                    if ($book['uri'] == CardDavBackend::PERSONAL_ADDRESSBOOK_URI) {
                        $defaultBookId = $book['id'];
                    }
                }

                if (is_null($defaultBookId)) {
                    $this->logger->warning("No default address book found. Falling back to the first in the list.");
                    $addressBookId = $addressBooks[0]['id'];
                } else {
                    $addressBookId = $defaultBookId;
                }
            } else {
                $addressBookId = $contactToCreate['oxpProperties']['addressBookId'];
            }

            $contactToCreateD = \Sabre\VObject\Reader::read($contactToCreate["vCard"]);
            // inspiration from https://github.com/nextcloud/server/blob/132f842f80b63ae0d782c7dbbd721836acbd29cb/apps/dav/lib/CardDAV/AddressBookImpl.php#L143
            // TODO this might create a URI that already exists. See
            // https://github.com/nextcloud/server/blob/132f842f80b63ae0d782c7dbbd721836acbd29cb/apps/dav/lib/CardDAV/AddressBookImpl.php#L234
            $uri = $contactToCreateD->uid . '.vcf';
            $this->backend->createCard($addressBookId, $uri, $contactToCreate["vCard"]);
            // We use the etag cache key as IDs. Inspiration from
            //  https://github.com/nextcloud/server/blob/master/apps/dav/lib/CardDAV/CardDavBackend.php#L705
            $contactMap[$creationId] = "$addressBookId#$uri";
        }

        return $contactMap;
    }

    private function createNewDefaultBook()
    {
        try {
            $this->backend->createAddressBook($this->principalUri, CardDavBackend::PERSONAL_ADDRESSBOOK_URI, [
                '{DAV:}displayname' => CardDavBackend::PERSONAL_ADDRESSBOOK_NAME,
            ]);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }

    public function destroy($ids, $accountId = null)
    {
        $this->logger->info("Destroying " . sizeof($ids) . " contacts for user " . $this->principalUri);
        $contactMap = [];

        foreach ($ids as $id) {
            // We return URIs made of addressBookId_OpenXPort_contactUri as ID. See Contact/set.
            if (!mb_strpos($id, "#")) {
                $this->logger->error("Invalid ID. It does not contain '#': " . $id);
                $contactMap[$id] = 0;
                continue;
            }
            list($addressBookId, $uri) = explode("#", $id);
            $contactMap[$id] = $this->backend->deleteCard($addressBookId, $uri);
        }

        return $contactMap;
    }

    /**
     * Query contacts with optional filters.
     * Supported filters: text, email, phone, inAddressBook
     * Returns the list of ContactCard ids that match the query.
    */
    public function query($accountId, $filter = null)
    {
        $this->logger->info("Querying contacts");

        if ($filter !== null && is_object($filter)) {
            $filter = json_decode(json_encode($filter), true);
        }

        $addressBooks = $this->getAddressBooks();
        $addressBookIds = [];

        foreach ($addressBooks as $i => $addressBook) {
            $addressBookIds[$i] = $addressBook['id'];
        }

        if (isset($filter['inAddressBook']) && !empty($filter['inAddressBook'])) {
            $addressBookIds = array_intersect($addressBookIds, $filter['inAddressBook']);
        }

        $db = \OC::$server->getDatabaseConnection();
        $contactsSql = 'SELECT * FROM `oc_cards` WHERE `addressbookid` IN (?)';
        $contactsQueryParams = array($addressBookIds);
        $contactsQueryTypes = array(\Doctrine\DBAL\Connection::PARAM_INT_ARRAY);
        $contactsQuery = $db->executeQuery(
            $contactsSql,
            $contactsQueryParams,
            $contactsQueryTypes
        );
        $contacts = $contactsQuery->fetchAll();

        $filteredIds = [];

        foreach ($contacts as $contact) {
            $addressBookId = $contact['addressbookid'];
            $cardUri = $contact['uri'];
            $id = "$addressBookId#$cardUri";

            // If no text/email/phone filters, include all contacts from the selected addressbooks
            if (!isset($filter['text']) && !isset($filter['email']) && !isset($filter['phone'])) {
                $filteredIds[] = $id;
                continue;
            }

            $vCard = \Sabre\VObject\Reader::read($contact['carddata']);
            $matches = true;

            // Filter: text (searches in FN, EMAIL, TEL fields)
            if (isset($filter['text'])) {
                $searchText = strtolower($filter['text']);
                $matches = false;

                if (isset($vCard->FN) && strpos(strtolower((string)$vCard->FN), $searchText) !== false) {
                    $matches = true;
                }

                if (!$matches && isset($vCard->EMAIL)) {
                    foreach ($vCard->EMAIL as $email) {
                        if (strpos(strtolower((string)$email), $searchText) !== false) {
                            $matches = true;
                            break;
                        }
                    }
                }

                if (!$matches && isset($vCard->TEL)) {
                    foreach ($vCard->TEL as $tel) {
                        if (strpos(strtolower((string)$tel), $searchText) !== false) {
                            $matches = true;
                            break;
                        }
                    }
                }
            }

            // Filter: email
            if ($matches && isset($filter['email'])) {
                $searchEmail = strtolower($filter['email']);
                $matches = false;

                if (isset($vCard->EMAIL)) {
                    foreach ($vCard->EMAIL as $email) {
                        if (strpos(strtolower((string)$email), $searchEmail) !== false) {
                            $matches = true;
                            break;
                        }
                    }
                }
            }

            // Filter: phone
            if ($matches && isset($filter['phone'])) {
                $searchPhone = $filter['phone'];
                $matches = false;

                if (isset($vCard->TEL)) {
                    foreach ($vCard->TEL as $tel) {
                        if (strpos((string)$tel, $searchPhone) !== false) {
                            $matches = true;
                            break;
                        }
                    }
                }
            }

            if ($matches) {
                $filteredIds[] = $id;
            }
        }

        return [
            'ids' => $filteredIds,
            'total' => count($filteredIds),
            'position' => 0
        ];
    }

    /**
     * Update existing contacts with new vCard data and return success status for each contact ID
     * Implements JMAP ContactCard/set update operation as per RFC 9553 (JSContact)
     */
    public function update($contactsToUpdate, $accountId = null)
    {
        if (is_null($contactsToUpdate)) {
            return [];
        }

        $this->logger->info("Updating " . count($contactsToUpdate) . " contacts for user " . $this->principalUri);
        $contactMap = [];

        foreach ($contactsToUpdate as $id => $contactData) {
            try {
                // Validate ID format: "addressBookId#uri"
                if (!mb_strpos($id, "#")) {
                    $this->logger->error("Invalid ID format. It does not contain '#': " . $id);
                    $contactMap[$id] = false;
                    continue;
                }

                // Split ID into addressBookId and uri
                list($addressBookId, $uri) = explode("#", $id, 2);

                // Verify contact exists
                $existingCard = $this->backend->getCard($addressBookId, $uri);
                if (is_null($existingCard) || empty($existingCard)) {
                    $this->logger->error("Contact with ID does not exist: " . $id);
                    $contactMap[$id] = false;
                    continue;
                }

                // Verify user has access to address book
                $addressBook = $this->backend->getAddressBookById($addressBookId);
                if (!$addressBook || $addressBook['principaluri'] !== $this->principalUri) {
                    $this->logger->error("Access denied to address book: " . $addressBookId);
                    $contactMap[$id] = false;
                    continue;
                }

                // Validate contact data contains vCard
                if (is_null($contactData) || !isset($contactData['vCard'])) {
                    $contactMap[$id] = false;
                    continue;
                }

                // Update contact in database
                $this->backend->updateCard($addressBookId, $uri, $contactData['vCard']);
                $contactMap[$id] = true;
                
            } catch (\Exception $e) {
                $this->logger->error("Failed to update contact " . $id . ": " . $e->getMessage());
                $contactMap[$id] = false;
            }
        }

        return $contactMap;
    }

    /**
     * Get changes since a specific state using Nextcloud's change tracking
     * Operation codes: 1 = created, 2 = updated, 3 = deleted
     * @see https://datatracker.ietf.org/doc/html/rfc8620#section-5.2 JMAP Core, /changes
     */
    public function getChanges($sinceState, $maxChanges = 1000, $accountId = null)
    {
        // Get user's address books
        $addressBooks = $this->getAddressBooks();
        
        if (is_null($addressBooks) || empty($addressBooks)) {
            return [
                'newState' => $sinceState,
                'hasMoreChanges' => false,
                'created' => [],
                'updated' => [],
                'destroyed' => []
            ];
        }
        
        $addressBookIds = array_column($addressBooks, 'id');
        
        // Query changes from oc_addressbookchanges table
        $db = \OC::$server->getDatabaseConnection();
        $query = "SELECT uri, synctoken, addressbookid, operation 
                FROM oc_addressbookchanges 
                WHERE addressbookid IN (?) 
                AND synctoken > ? 
                ORDER BY synctoken ASC 
                LIMIT ?";
        
        $result = $db->executeQuery(
            $query, 
            [$addressBookIds, (int)$sinceState, $maxChanges + 1],
            [\Doctrine\DBAL\Connection::PARAM_INT_ARRAY,
            \Doctrine\DBAL\ParameterType::INTEGER,
            \Doctrine\DBAL\ParameterType::INTEGER]
        );
        $rows = $result->fetchAll();

        // Check if more changes exist beyond maxChanges limit
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
            $contactId = $row['addressbookid'] . '#' . $row['uri'];
            
            // Map operation code to change type
            $operationMap = [1 => 'created', 2 => 'updated', 3 => 'destroyed'];
            if (isset($operationMap[$row['operation']])) {
                $changes[$operationMap[$row['operation']]][] = $contactId;
            }
        }

        return array_merge($changes, [
            'newState' => $newState,
            'hasMoreChanges' => $hasMoreChanges
        ]);
    }

    /**
     * Get current state for contacts
     * Returns the maximum synctoken from contact changes
     */
    public function getCurrentState($accountId = null)
    {
        try {
            $addressBooks = $this->getAddressBooks();
            
            if (empty($addressBooks)) {
                return "0";
            }
            
            $addressBookIds = array_column($addressBooks, 'id');
            $placeholders = implode(',', array_fill(0, count($addressBookIds), '?'));
            
            $db = \OC::$server->getDatabaseConnection();
            $query = "SELECT MAX(synctoken) as current_state 
                    FROM oc_addressbookchanges 
                    WHERE addressbookid IN ($placeholders)";
            
            $stmt = $db->prepare($query);
            $stmt->execute($addressBookIds);
            $result = $stmt->fetch();
            
            return $result && $result['current_state'] ? (string)$result['current_state'] : "0";
            
        } catch (\Exception $e) {
            $this->logger->error("Failed to get current state: " . $e->getMessage());
            return "0";
        }
    }
}
