<?php

namespace OpenXPort\DataAccess;

use OCA\DAV\CardDAV\CardDavBackend;

class NextcloudAddressbookDataAccess extends AbstractDataAccess
{
    private $backend;
    private $logger;
    private $principalUri;

    public function __construct(CardDavBackend $backend)
    {
        $this->backend = $backend;
        $this->logger = \OpenXPort\Util\Logger::getInstance();
        $this->principalUri = 'principals/users/' . $_SERVER['PHP_AUTH_USER'];
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
        // TODO: Implement me
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
            $this->logger->warning("AddressBook/set did not contain any data for creating for user " . $this->principalUri);
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
                $bookMap[$creationId] = $this->backend->createAddressBook($this->principalUri, $uri, $bookToCreate);
            }
        }

        return $bookMap;
    }

    public function destroy($ids, $accountId = null)
    {
        $bookMap = [];
        if (is_null($ids)) {
            $this->logger->warning("AddressBook/set did not contain any data for destroying for user " . $this->principalUri);
            return $bookMap;
        }
        $this->logger->info("Destroying " . sizeof($ids) . " address books for user " . $this->principalUri);

        foreach ($ids as $id) {
            $bookMap[$id] = $this->backend->deleteAddressBook($id);
        }

        return $bookMap;
    }

    public function query($accountId, $filter = null)
    {
        // TODO: Implement me
    }
}
