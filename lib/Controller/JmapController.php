<?php

namespace OCA\JMAP\Controller;

use OCP\IRequest;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\ApiController;
use OCA\JMAP\JMAP\CalendarEvent\CalendarEvent;
use OCA\JMAP\JMAP\Adapter\JmapCalendarEventAdapter;

class JmapController extends ApiController
{
    // Define version
    private $OXP_VERSION = '1.3.0';

    private $userId;

    private $oxpConfig;

    private $accessors;

    private $adapters;

    private $mappers;

    private function init()
    {
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
        // Decode JSON post body here in case the debug capability is included
        $this->jmapRequest = \OpenXPort\Util\HttpUtil::getRequestBody();

        // Initialize logging
        \OpenXPort\Util\Logger::init($this->oxpConfig, $this->jmapRequest);
        $logger = \OpenXPort\Util\Logger::getInstance();

        $logger->notice("Running PHP v" . phpversion() . ", TODO v NEXTCLOUD, Plugin v" . $this->OXP_VERSION);

        $this->accessors = array(
            "Contacts" => new \OpenXPort\DataAccess\NextcloudContactDataAccess(),
            "AddressBooks" => new \OpenXPort\DataAccess\NextcloudAddressbookDataAccess(),
            "Calendars" => null,
            "CalendarEvents" => new \OpenXPort\DataAccess\NextcloudCalendarEventDataAccess(),
            "Tasks" => null,
            "Notes" => null,
            "Identities" => null,
            "Filters" => null,
            "StorageNodes" => null,
            "ContactGroups" => null,
        );

        $this->adapters = array(
            "Contacts" => new \OpenXPort\Adapter\NextcloudVCardAdapter(),
            "AddressBooks" => new \OpenXPort\Adapter\NextcloudAddressbookAdapter(),
            "Calendars" => null,
            "CalendarEvents" => new \OpenXPort\Adapter\NextcloudCalendarEventAdapter(),
            "Tasks" => null,
            "Notes" => null,
            "Identities" => null,
            "Filters" => null,
            "StorageNodes" => null,
            "ContactGroups" => null,
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
        );
    }

    public function __construct($appName, IRequest $request, $userId)
    {
        parent::__construct($appName, $request);
        $this->userId = $userId;
        $this->init();
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
        $server = new \OpenXPort\Jmap\Core\Server($this->accessors, $this->adapters, $this->mappers, $this->oxpConfig);
        $server->handleJmapRequest($this->jmapRequest);

        // Currently we use die here, since we need to stop any execution after '$server->listen();'.
        // That's because we don't have a return statement for this function.
        // When there is no return statement in PHP (or, equivalently, there's simply 'return;'),
        // then the function returns null
        // In our case here when the function returns null, it gets appended right next to the JMAP response,
        // delivered by '$server->listen();' and we thus get an invalid JSON (due to the appended null after the JSON)
        die;
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
    public function request()
    {
        $server = new \OpenXPort\Jmap\Core\Server($this->accessors, $this->adapters, $this->mappers, $this->oxpConfig);
        $server->handleJmapRequest($this->jmapRequest);

        // Currently we use die here, since we need to stop any execution after '$server->listen();'.
        // That's because we don't have a return statement for this function.
        // When there is no return statement in PHP (or, equivalently, there's simply 'return;'),
        // then the function returns null
        // In our case here when the function returns null, it gets appended right next to the JMAP response,
        // delivered by '$server->listen();' and we thus get an invalid JSON (due to the appended null after the JSON)
        die;
    }
}
