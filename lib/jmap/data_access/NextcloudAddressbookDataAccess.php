<?php

namespace OpenXPort\DataAccess;

class NextcloudAddressbookDataAccess extends AbstractDataAccess
{
    public function getAll($accountId = null)
    {
        $db = \OC::$server->getDatabaseConnection();

        $userUid = $_SERVER['PHP_AUTH_USER'];

        $addressbooksSql = 'SELECT * FROM `oc_addressbooks` WHERE `principaluri` = ?';
        $addressbooksQueryParams = array('principals/users/' . $userUid);
        $addressbooksResult = $db->executeQuery($addressbooksSql, $addressbooksQueryParams);
        $addressbooks = $addressbooksResult->fetchAll();

        return $addressbooks;
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
