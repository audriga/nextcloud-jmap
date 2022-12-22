<?php

namespace OCA\JMAP\Controller;

use OCP\IRequest;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Controller;
use OCA\JMAP\JMAP\CalendarEvent\CalendarEvent;
use OCA\JMAP\JMAP\Adapter\JmapCalendarEventAdapter;

class JmapController extends Controller
{
    private $userId;

    private function init()
    {
        // iCal lib
        require(__DIR__ . '/../../icalendar/zapcallib.php');

        // Print debug output via API on error
        // NOTE: Do not use on public-facing setups
        $handler = new \OpenXPort\Jmap\Core\ErrorHandler();
        $handler->setHandlers();
    }

    public function __construct($AppName, IRequest $request, $UserId)
    {
        parent::__construct($AppName, $request);
        $this->userId = $UserId;
        //print_r("UserId is: " . $UserId . " and userId is: " . $this->userId);
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
    public function index()
    {
        return new DataResponse('OpenXPort JMAP API for Nextcloud, powered by NGI DAPSI, is enabled.');
        // Use TemplateResponse in case we want to have a UI.
        //return new TemplateResponse('jmap', 'index');
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
    public function jmap()
    {
        $this->init();

        $accessors = array(
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

        $adapters = array(
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

        $mappers = array(
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

        $server = new \OpenXPort\Jmap\Core\Server($accessors, $adapters, $mappers);
        $server->listen();

        // Currently we use die here, since we need to stop any execution after '$server->listen();'.
        // That's because we don't have a return statement for this function.
        // When there is no return statement in PHP (or, equivalently, there's simply 'return;'),
        // then the function returns null
        // In our case here when the function returns null, it gets appended right next to the JMAP response,
        // delivered by '$server->listen();' and we thus get an invalid JSON (due to the appended null after the JSON)
        die;
    }

    // private function createContact()
    // {
    //     // TODO implement writing of individual contacts
    // }

    // private function getAppointments()
    // {
    //     /**
    //      * Access all data from the DB and return it in the response
    //        (currently running a hardcoded SQL query to read all appointments data from the db)
    //      * Warning: the SQL query returns all entries from the 'oc_calendarobjects' table
    //        (which includes BOTH appointments (VEVENT) and tasks (VTODO))
    //     */
    //     $db = \OC::$server->getDatabaseConnection();
    //     $sql = 'select * from oc_calendarobjects';
    //     $result = $db->executeQuery($sql);


    //     $list = [];
    //     $notFound = [];

    //     foreach ($result->fetchAll() as $appointment) {
    //         // Consume iCal data and convert it to JSEvent objects for JMAP for Calendar
    //         $icalobj = new \ZCiCal($appointment['calendardata']);

    //         foreach ($icalobj->tree->child as $node) {
    //             // Differentiate if the iCal component is an event and only then use it
    //             // for transformation to JMAP CalendarEvent (i.e. ignore VTODO, etc.)
    //             if ($node->getName() == "VEVENT") {
    //                 $adapter = new OpenXPort\JmapCalendarEventAdapter($node);

    //                 $jmapCalendarEvent = new CalendarEvent();
    //                 $jmapCalendarEvent->setCalendarId($appointment['calendarid']);
    //                 $jmapCalendarEvent->setStart($adapter->getDTStart());
    //                 $jmapCalendarEvent->setDuration($adapter->getDuration());
    //                 $jmapCalendarEvent->setStatus($adapter->getStatus());
    //                 $jmapCalendarEvent->setType("jsevent");
    //                 $jmapCalendarEvent->setUid($adapter->getUid());
    //                 $jmapCalendarEvent->setProdId($adapter->getProdId());
    //                 $jmapCalendarEvent->setCreated($adapter->getCreated());
    //                 $jmapCalendarEvent->setUpdated($adapter->getLastModified());
    //                 $jmapCalendarEvent->setSequence($adapter->getSequence());
    //                 $jmapCalendarEvent->setTitle($adapter->getSummary());
    //                 $jmapCalendarEvent->setDescription($adapter->getDescription());
    //                 $jmapCalendarEvent->setLocations($adapter->getLocation());
    //                 $jmapCalendarEvent->setKeywords($adapter->getCategories());
    //                 $jmapCalendarEvent->setRecurrenceRule($adapter->getRRule());
    //                 $jmapCalendarEvent->setRecurrenceOverrides($adapter->getExDate());
    //                 $jmapCalendarEvent->setPriority($adapter->getPriority());
    //                 $jmapCalendarEvent->setPrivacy($adapter->getClass());
    //                 $jmapCalendarEvent->setTimeZone($adapter->getTimeZone());

    //                 array_push($list, $jmapCalendarEvent);
    //             }
    //         }


    //         //array_push($list, $icalobj);
    //     }

    //     $args = array("state" => "1234", "list" => $list, "notFound" => $notFound, "accountId" => "blaaaa");
    //     $invocation = array("CalendarEvent/get", $args, "0");
    //     $res = array("methodResponses" => array($invocation), "sessionState" => "0");


    //     return $res;
    // }
}
