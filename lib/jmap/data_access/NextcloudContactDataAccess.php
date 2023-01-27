<?php

namespace OpenXPort\DataAccess;

use OCA\DAV\CardDAV\CardDavBackend;

class NextcloudContactDataAccess extends AbstractDataAccess
{
    private $userId;
    private $backend;
    private $logger;

    public function __construct(CardDavBackend $backend)
    {
        $this->backend = $backend;
        $this->logger = \OpenXPort\Util\Logger::getInstance();
    }

    private function getAddressBooks($db)
    {
        // In order to read the user's contact data, we need the user's UID which luckily is the user's
        // Nextcloud username.
        // We can take that from the Basic Auth credentials, sent to us within the JMAP request.
        // The username is thus to be found in '$_SERVER['PHP_AUTH_USER']'.
        $this->userUid = $_SERVER['PHP_AUTH_USER'];

        // First read all of the user's own addressbooks' IDs
        $ownAddressbooksSql = 'SELECT id FROM `oc_addressbooks` WHERE `principaluri` = ?';
        // Create the principal URI, required as a SQL parameter, so that we can obtain the user's
        // addressbooks with the help of the user's username that we got from above.
        $ownAddressbooksQueryParams = array('principals/users/' . $this->userUid);
        // Execute the query as a prepared statement (protect against SQL injection)
        $ownAddressbooksResult = $db->executeQuery($ownAddressbooksSql, $ownAddressbooksQueryParams);
        // Collect all the addressbook IDs
        $addressBookIds = $ownAddressbooksResult->fetchAll();

        // Since we receive an array of arrays holding the addressbook IDs, we want to restructure
        // it such that we only have one array, containing all IDs. That's why we flatten the result
        // that we received in the foreach below.
        foreach ($addressBookIds as $i => $addressBookId) {
            $addressBookIds[$i] = $addressBookId['id'];
        }

        return $addressBookIds;
    }

    public function getAll($accountId = null)
    {
        $this->logger->info("Getting contacts");
        // Obtain a database connection in order to be able to query the DB and read contact data from it
        $db = \OC::$server->getDatabaseConnection();

        $addressBookIds = $this->getAddressBooks($db);

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
            // We return URIs made of addressBookId_OpenXPort_contactUri as ID. See Contact/set.
            $id = $contact['addressbookid'] . "_OpenXPort_" . $contact['uri'];
            $res[$id] = $contact['carddata'];
        }

        return $res;
    }

    public function get($ids, $accountId = null)
    {
        throw new \BadMethodCallException("getting contacts by ID not implemented for Card/get.");
    }

    public function create($contactsToCreate, $accountId = null)
    {
        $this->logger->info("Creating " . sizeof($contactsToCreate) . " contacts for user " . $accountId);

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
            } else {
                $db = \OC::$server->getDatabaseConnection();
                // assume that the first address book is the one we want to create contacts in
                // TODO this assumption might be incorrect
                // TODO use addressBookId from contact in request
                $defaultAddressBookId = $this->getAddressBooks($db)[0];
                $contactToCreateD = \Sabre\VObject\Reader::read($contactToCreate);
                // inspiration from https://github.com/nextcloud/server/blob/132f842f80b63ae0d782c7dbbd721836acbd29cb/apps/dav/lib/CardDAV/AddressBookImpl.php#L143
                // TODO this might create a URI that already exists. See
                // https://github.com/nextcloud/server/blob/132f842f80b63ae0d782c7dbbd721836acbd29cb/apps/dav/lib/CardDAV/AddressBookImpl.php#L234
                $uri = $contactToCreateD->uid . '.vcf';
                $this->backend->createCard($defaultAddressBookId, $uri, $contactToCreate);
                // CardDAV bakend does not expose us internal contact ids but rather URIs that are unique in an
                // addressbook only. So we return URIs made of addressBookId_OpenXPort_contactUri
                $contactMap[$creationId] = $defaultAddressBookId . "_OpenXPort_" . $uri;
            }
        }

        return $contactMap;
    }

    public function destroy($ids, $accountId = null)
    {
        $this->logger->info("Destroying " . sizeof($ids) . " contacts for user " . $accountId);
        $contactMap = [];

        foreach ($ids as $id) {
            // We return URIs made of addressBookId_OpenXPort_contactUri as ID. See Contact/set.
            if (!mb_strpos($id, "_OpenXPort_")) {
                throw new \InvalidArgumentException("Invalid ID. It does not contain '_OpenXPort_: " . $id);
            }
            list($addressBookId, $uri) = explode("_OpenXPort_", $id);
            $contactMap[$id] = $this->backend->deleteCard($addressBookId, $uri);
        }

        return $contactMap;
    }

    public function query($accountId, $filter = null)
    {
        throw new \BadMethodCallException("Card/query not implemented.");
    }
}
