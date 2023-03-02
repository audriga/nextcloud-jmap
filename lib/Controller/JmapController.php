<?php

namespace OCA\JMAP\Controller;

use OCP\IRequest;
use OCP\AppFramework\ApiController;
use OCP\AppFramework\Http\DataDisplayResponse;
use OCA\JMAP\JMAP\CalendarEvent\CalendarEvent;
use OCA\JMAP\JMAP\Adapter\JmapCalendarEventAdapter;
use OCA\DAV\CardDAV\CardDavBackend;

class JmapController extends ApiController
{
    // Define version
    private $OXP_VERSION = '1.3.0';

    private $userId;

    private $oxpConfig;

    private $accessors;

    private $adapters;

    private $mappers;

    private $jmapRequest;

    private function init()
    {
        // Initialize logging
        \OpenXPort\Util\Logger::init($this->oxpConfig, $this->jmapRequest);
        $logger = \OpenXPort\Util\Logger::getInstance();

        $logger->notice("Running PHP v" . phpversion() . ", TODO v NEXTCLOUD, Plugin v" . $this->OXP_VERSION);

        $this->accessors = array(
            "Contacts" => new \OpenXPort\DataAccess\NextcloudContactDataAccess($this->davBackend),
            "AddressBooks" => new \OpenXPort\DataAccess\NextcloudAddressbookDataAccess($this->davBackend),
            "Calendars" => null,
            "CalendarEvents" => new \OpenXPort\DataAccess\NextcloudCalendarEventDataAccess(),
            "Tasks" => null,
            "Notes" => null,
            "Identities" => null,
            "Filters" => null,
            "StorageNodes" => null,
            "ContactGroups" => null,
            "Cards" => new \OpenXPort\DataAccess\NextcloudContactDataAccess($this->davBackend)
        );

        $this->adapters = array(
            "Contacts" => new \OpenXPort\Adapter\NextcloudJSContactVCardAdapter(),
            "AddressBooks" => new \OpenXPort\Adapter\NextcloudAddressbookAdapter(),
            "Calendars" => null,
            "CalendarEvents" => new \OpenXPort\Adapter\NextcloudCalendarEventAdapter(),
            "Tasks" => null,
            "Notes" => null,
            "Identities" => null,
            "Filters" => null,
            "StorageNodes" => null,
            "ContactGroups" => null,
            "Cards" => new \OpenXPort\Adapter\NextcloudJSContactVCardAdapter()
        );

        $this->mappers = array(
            "Contacts" => new \OpenXPort\Mapper\VCardMapper(),
            "AddressBooks" => new \OpenXPort\Mapper\NextcloudAddressbookMapper(),
            "Calendars" => null,
            "CalendarEvents" => new \OpenXPort\Mapper\NextcloudCalendarEventMapper(),
            "Tasks" => null,
            "Notes" => null,
            "Identities" => null,
            "Filters" => null,
            "StorageNodes" => null,
            "ContactGroups" => null,
            "Cards" => new \OpenXPort\Mapper\JSContactVCardMapper()
        );
    }

    public function __construct($appName, IRequest $request, CardDavBackend $davBackend, $userId)
    {
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

        $this->davBackend = $davBackend;
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
        $this->init();

        $server = new \OpenXPort\Jmap\Core\Server($this->accessors, $this->adapters, $this->mappers, $this->oxpConfig);
        $server->handleJmapRequest($this->jmapRequest);

        // Currently we return an empty DataDisplayResponse here.
        // That's because we use echo for appending the JSON to the output.
        // The result of this function gets appended right next to the JMAP response,
        // delivered by '$server->listen();'
        // DataDisplayResponse is one of the few that prints nothing when there is no output.
        return new DataDisplayResponse();
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

        $this->init();

        $server = new \OpenXPort\Jmap\Core\Server($this->accessors, $this->adapters, $this->mappers, $this->oxpConfig);
        $server->handleJmapRequest($this->jmapRequest);

        // Currently we return an empty DataDisplayResponse here.
        // That's because we use echo for appending the JSON to the output.
        // The result of this function gets appended right next to the JMAP response,
        // delivered by '$server->listen();'
        // DataDisplayResponse is one of the few that prints nothing when there is no output.
        return new DataDisplayResponse();
    }
}
