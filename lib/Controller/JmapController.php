<?php

namespace OCA\JMAP\Controller;

use OCP\IRequest;
use OCP\AppFramework\ApiController;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataDisplayResponse;
use OCP\IGroupManager;
use OCP\IUserSession;
use OCP\IUserManager;
use OCA\JMAP\JMAP\CalendarEvent\CalendarEvent;
use OCA\JMAP\JMAP\Adapter\JmapCalendarEventAdapter;
use OCA\DAV\CardDAV\CardDavBackend;
use OCA\DAV\CalDAV\CalDavBackend;

class JmapController extends ApiController
{
    // Define version
    private $OXP_VERSION = '1.3.0';

    private $userId;

    private $oxpConfig;

    private $logger;

    private $jmapRequest;
    /** @var IUserSession */
    private $userSession;
    /** @var IUserManager */
    private $userManager;
    /** @var IGroupManager|Manager */
    private $groupManager;
    /** @var CardDavBackend */
    private $cardDavBackend;
    /** @var CalDavBackend */
    private $calDavBackend;

    private function init()
    {
        // Initialize logging
        \OpenXPort\Util\Logger::init($this->oxpConfig, $this->jmapRequest);
        $this->logger = \OpenXPort\Util\Logger::getInstance();

        $this->logger->notice("Running PHP v" . phpversion() . ", TODO v NEXTCLOUD, Plugin v" . $this->OXP_VERSION);

        // If we are dealing with admin auth credentials, user first part as the admin username for login
        if (mb_strpos($_SERVER['PHP_AUTH_USER'], "*")) {
            $error = $this->impersonate(explode("*", $_SERVER['PHP_AUTH_USER'])[1]);
            if (!empty($error)) {
                return new DataDisplayResponse($error[0], $error[1]);
            }
        }

        $accessors = array(
            "Contacts" => new \OpenXPort\DataAccess\NextcloudContactDataAccess($this->cardDavBackend),
            "AddressBooks" => new \OpenXPort\DataAccess\NextcloudAddressbookDataAccess($this->cardDavBackend),
            "Calendars" => new \OpenXPort\DataAccess\NextcloudCalendarDataAccess($this->calDavBackend),
            "CalendarEvents" => new \OpenXPort\DataAccess\NextcloudCalendarEventDataAccess($this->calDavBackend),
            "Tasks" => null,
            "Notes" => null,
            "Identities" => null,
            "Filters" => null,
            "StorageNodes" => null,
            "ContactGroups" => null,
            "Cards" => new \OpenXPort\DataAccess\NextcloudContactDataAccess($this->cardDavBackend)
        );

        $adapters = array(
            "Contacts" => new \OpenXPort\Adapter\NextcloudJSContactVCardAdapter(),
            "AddressBooks" => new \OpenXPort\Adapter\NextcloudAddressbookAdapter(),
            "Calendars" => new \OpenXPort\Adapter\NextcloudCalendarAdapter(),
            "CalendarEvents" => new \OpenXPort\Adapter\JSCalendarICalendarAdapter(),
            "Tasks" => null,
            "Notes" => null,
            "Identities" => null,
            "Filters" => null,
            "StorageNodes" => null,
            "ContactGroups" => null,
            "Cards" => new \OpenXPort\Adapter\NextcloudJSContactVCardAdapter()
        );

        $mappers = array(
            "Contacts" => new \OpenXPort\Mapper\VCardMapper(),
            "AddressBooks" => new \OpenXPort\Mapper\NextcloudAddressbookMapper(),
            "Calendars" => new \OpenXPort\Mapper\NextcloudCalendarMapper(),
            "CalendarEvents" => new \OpenXPort\Mapper\JSCalendarICalendarMapper(),
            "Tasks" => null,
            "Notes" => null,
            "Identities" => null,
            "Filters" => null,
            "StorageNodes" => null,
            "ContactGroups" => null,
            "Cards" => new \OpenXPort\Mapper\JSContactVCardMapper()
        );

        $accountData = [
            'accountId' => $this->userSession->getUser()->getUID(),
            'username' => $this->userSession->getUser()->getUID(),
            'accountCapabilities' => []
        ];
        $session = \OpenXPort\Util\NextcloudSessionUtil::createSession($accountData);

        $server = new \OpenXPort\Jmap\Core\Server($accessors, $adapters, $mappers, $this->oxpConfig, $session);
        $server->handleJmapRequest($this->jmapRequest);

        // Currently we return an empty DataDisplayResponse here.
        // That's because we use echo for appending the JSON to the output.
        // The result of this function gets appended right next to the JMAP response,
        // delivered by '$server->listen();'
        // DataDisplayResponse is one of the few that prints nothing when there is no output.
        return new DataDisplayResponse();
    }

    public function __construct(
        $appName,
        IRequest $request,
        IUserManager $userManager,
        IGroupManager $groupManager,
        IUserSession $userSession,
        CardDavBackend $cardDavBackend,
        CalDavBackend $calDavBackend,
        $userId
    ) {
        parent::__construct($appName, $request);
        $this->userId = $userId;

        // Print debug output via API on error
        // NOTE: Do not use on public-facing setups
        $handler = new \OpenXPort\Jmap\Core\ErrorHandler();
        $handler->setHandlers();

        // Build config
        $configDefault = include(__DIR__ . '/../../config/config.default.php');
        $configFile = __DIR__ . '/../../config/config.php';
        $this->oxpConfig = $configDefault;

        if (file_exists($configFile)) {
            $configUser = include($configFile);
            if (is_array($configUser)) {
                $this->oxpConfig = array_merge($configDefault, $configUser);
            }
        };

        $this->userSession = $userSession;
        $this->userManager = $userManager;
        $this->groupManager = $groupManager;
        $this->cardDavBackend = $cardDavBackend;
        $this->calDavBackend = $calDavBackend;
    }

    /**
     * Heavily inspired from
     * https://github.com/nextcloud/impersonate/blob/master/lib/Controller/SettingsController.php#L73
     *
     * @UseSession
     * @NoAdminRequired
     */
    private function impersonate($userId)
    {
        /** @var IUser $impersonator */
        $impersonator = $this->userSession->getUser();

        $this->logger->notice(sprintf('User %s trying to impersonate user %s', $impersonator->getUID(), $userId));

        $impersonatee = $this->userManager->get($userId);
        if ($impersonatee === null) {
            return ['User not found', Http::STATUS_NOT_FOUND];
        }

        if (
            !$this->groupManager->isAdmin($impersonator->getUID())
            && !$this->groupManager->getSubAdmin()->isUserAccessible($impersonator, $impersonatee)
        ) {
            return ['Insufficient permissions to impersonate user', Http::STATUS_FORBIDDEN];
        }

        $authorized = $this->oxpConfig["adminGroups"];
        if (empty($authorized)) {
            return ['No groups configured for admin auth.', Http::STATUS_FORBIDDEN];
        } else {
            $userGroups = $this->groupManager->getUserGroupIds($impersonator);

            if (!array_intersect($userGroups, $authorized)) {
                return ['Insufficient permissions to impersonate user', Http::STATUS_FORBIDDEN];
            }
        }

        if ($impersonatee->getLastLogin() === 0) {
            return ['Cannot impersonate the user because it was never logged in', Http::STATUS_FORBIDDEN];
        }

        if ($impersonatee->getUID() === $impersonator->getUID()) {
            return ['Cannot impersonate yourself', Http::STATUS_FORBIDDEN];
        }

        $this->logger->notice(sprintf('Changing to user %s', $userId));

        // Not needed?
        //if ($this->session->get('oldUserId') === null) {
        //    $this->session->set('oldUserId', $impersonator->getUID());
        //}

        $this->userSession->setUser($impersonatee);

        return [];
    }

    /**
     * CAUTION: the @Stuff turns off security checks; for this page no admin is
     *          required and no CSRF check. If you don't know what CSRF is, read
     *          it up in the docs or you might create a security hole. This is
     *          basically the only required method to add this exemption, don't
     *          add it to any other method if you don't exactly know what it does
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function session()
    {
        return $this->init();
    }

    /**
     * CAUTION: the @Stuff turns off security checks; for this page no admin is
     *          required and no CSRF check. If you don't know what CSRF is, read
     *          it up in the docs or you might create a security hole. This is
     *          basically the only required method to add this exemption, don't
     *          add it to any other method if you don't exactly know what it does
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function request($using, $methodCalls)
    {
        // Nextcloud gives us a nice array. However, our code assumes stdClass -> convert it!
        // This is not so nice
        $requestInput = json_decode(json_encode(array("using" => $using, "methodCalls" => $methodCalls)));

        $this->jmapRequest = new \OpenXPort\Jmap\Core\Request($requestInput);

        return $this->init();
    }
}
