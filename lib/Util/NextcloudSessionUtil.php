<?php

namespace OpenXPort\Util;

use OpenXPort\Jmap\Core\Account;
use OpenXPort\Jmap\Core\Session;

class NextcloudSessionUtil extends SessionUtil
{
    public static function createSession($accountData, $baseUrl = null)
    {
        if (!isset($accountData) || empty($accountData)) {
            throw new \Exception("Provided account data is empty");
        }

        if (!isset($accountData['username'])) {
            throw new \Exception("\"username\" not found in account data");
        }

        if (!isset($accountData['accountId'])) {
            throw new \Exception("\"accountId\" not found in account data");
        }

        if (!isset($accountData['accountCapabilities'])) {
            throw new \Exception("\"accountCapabilities\" not found in account data");
        }

        $accountId = $accountData['accountId'];
        $username = $accountData['username'];

        // Map each account capability's name to the capability's options and put this mapping
        // as an element into a new array (initially empty array)
        // Example:
        // * Before: [new SubmissionCapability()]
        // * After (in JSON): {"urn:ietf:params:jmap:submission" : {"maxDelaySend": 0}}
        $accountCapabilities = array_reduce($accountData['accountCapabilities'], function ($result, $item) {
            $result[$item->getName()] = (object) $item->getCapabilities();
            return $result;
        }, []);

        $sessionAccount = new Account();
        $sessionAccount->setName($username);

        // Assuming true and false as default values for isPersonal and isReadOnly for now
        $sessionAccount->setIsPersonal(true);
        $sessionAccount->setIsReadOnly(false);

        $sessionAccount->setAccountCapabilities($accountCapabilities);

        $accounts = [$accountId => $sessionAccount];

        // We construct "primaryAccounts" of the JMAP session object by taking the array keys of
        // the account capabilities (i.e., the account capability names) and mapping them all to accountId
        $primaryAccounts = array_reduce(array_keys($accountCapabilities), function ($result, $item) use ($accountId) {
            $result[$item] = $accountId;
            return $result;
        }, []);

        if ($baseUrl === null) {
            $requestPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
            $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https://' : 'http://';
            $baseUrl = $protocol . $_SERVER['HTTP_HOST'] . dirname($requestPath);
        }

        // No upload/download endpoints are implemented yet in this app.
        // These are placeholder URLs so clients (e.g. Parula) that require
        // non-empty, well-formed session URLs don't fail during setup.
        $uploadUrl = $baseUrl . '/upload?accountId={accountId}';
        $downloadUrl = $baseUrl . '/download?accountId={accountId}&blobId={blobId}&type={type}&name={name}';

        return new Session($accounts, $primaryAccounts, $username, $uploadUrl, $downloadUrl);
    }
}
