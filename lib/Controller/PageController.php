<?php

namespace OCA\JMAP\Controller;

use OCP\IRequest;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Controller;
use JeroenDesloovere\VCard\VCardParser;
// JMAP-related classes
use OCA\JMAP\JMAP\Contact\Contact;
use OCA\JMAP\JMAP\Adapter\JmapContactAdapter;
use OCA\JMAP\JMAP\CalendarEvent\CalendarEvent;
use OCA\JMAP\JMAP\Adapter\JmapCalendarEventAdapter;

/* This is for displaying a page in the UI. TODO Currently unused. */
class PageController extends Controller
{
    private $userId;

    public function __construct($AppName, IRequest $request, $UserId)
    {
        parent::__construct($AppName, $request);
        $this->userId = $UserId;
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
    public function jmap($using, $methodCalls)
    {
        if (
            is_array($using) && !is_null($using) && !empty($using) &&
            in_array("urn:ietf:params:jmap:contacts", $using)
        ) {
            if (
                is_array($methodCalls) && !is_null($methodCalls) && !empty($methodCalls) && !is_null($methodCalls[0][2])
            ) {
                if (strcmp($methodCalls[0][0], "Contact/get") === 0) {
                    $response = $this->getContacts();
                    return new JSONResponse($response);
                } elseif (strcmp($methodCalls[0][0], "Contact/set") === 0) {
                    // TODO: implement logic for writing individual contacts via 'Contact/set'
                } else {
                    $args = array("type" => "unknownMethod");
                    $invocation = array("error", $args, "0");
                    $errorRes = array("methodResponses" => array($invocation), "sessionState" => "0");
                    return new JSONResponse($errorRes);
                }
            } else {
                return new JSONResponse(array("type" => "urn:ietf:params:jmap:error:notRequest", "status" => 400,
                    "detail" => "Not a valid JMAP request"));
            }
        } elseif (
            is_array($using) && !is_null($using) && !empty($using) && in_array("urn:ietf:params:jmap:calendars", $using)
        ) {
            if (
                is_array($methodCalls) && !is_null($methodCalls) && !empty($methodCalls) && !is_null($methodCalls[0][2])
            ) {
                if (strcmp($methodCalls[0][0], "CalendarEvent/get") === 0) {
                    $response = $this->getAppointments();
                    return new JSONResponse($response);
                } else {
                    $args = array("type" => "unknownMethod");
                    $invocation = array("error", $args, "0");
                    $errorRes = array("methodResponses" => array($invocation), "sessionState" => "0");
                    return new JSONResponse($errorRes);
                }
            } else {
                return new JSONResponse(
                    array(
                        "type" => "urn:ietf:params:jmap:error:notRequest",
                        "status" => 400,
                        "detail" => "Not a valid JMAP request"
                    )
                );
            }
        } else {
            return new JSONResponse(
                array(
                    "type" => "urn:ietf:params:jmap:error:unknownCapability",
                    "status" => 400,
                    "detail" => "Unknown capability"
                )
            );
        }
    }

    private function getContacts()
    {
        // Access all data from the DB and return it in the response
        // (currently running a hardcoded SQL query to read all contacts data from the db)
        $db = \OC::$server->getDatabaseConnection();
        $sql = 'select * from oc_cards';
        $result = $db->executeQuery($sql);


        $list = [];
        $notFound = [];

        foreach ($result->fetchAll() as $contact) {
            // Check for entries from the DB which represent NC system users (and skip them)
            $sqlForSystemUsers = 'select uid from oc_users';
            $systemUsersSQLResult = $db->executeQuery($sqlForSystemUsers)->fetchAll();
            // $systemUsersSQLResult is an array of arrays
            // this is why we iterate with foreach and check in each iteration with in_array()
            foreach ($systemUsersSQLResult as $systemUser) {
                if (in_array($contact['uid'], $systemUser)) {
                    continue 2;
                }
            }


            $parser = new VCardParser($contact['carddata']);
            $vCard = $parser->getCardAtIndex(0);

            // The print_r below is for debugging purposes
            //print_r($vCard);

            $adapter = new JmapContactAdapter($vCard);

            $jmapContact = new Contact();
            $jmapContact->setPrefix($adapter->getPrefix());
            $jmapContact->setFirstName($adapter->getFirstName());
            $jmapContact->setLastName($adapter->getLastName());
            $jmapContact->setSuffix($adapter->getSuffix());
            $jmapContact->setBirthday($adapter->getBirthday());
            $jmapContact->setCompany($adapter->getCompany());
            $jmapContact->setJobTitle($adapter->getJobTitle());
            $jmapContact->setEmails($adapter->getEmails());
            $jmapContact->setPhones($adapter->getPhones());
            $jmapContact->setOnline($adapter->getOnline());
            $jmapContact->setAddresses($adapter->getAddresses());
            $jmapContact->setNotes($adapter->getNotes());

            array_push($list, $jmapContact);
        }

        $args = array("state" => "1234", "list" => $list, "notFound" => $notFound, "accountId" => "blaaaa");
        $invocation = array("Contact/get", $args, "0");
        $res = array("methodResponses" => array($invocation), "sessionState" => "0");


        return $res;
    }

    private function createContact()
    {
        // TODO implement writing of individual contacts
    }

    private function getAppointments()
    {
        /**
         * Access all data from the DB and return it in the response
         *  (currently running a hardcoded SQL query to read all appointments data from the db)
         * Warning: the SQL query returns all entries from the 'oc_calendarobjects' table
         *  (which includes BOTH appointments (VEVENT) and tasks (VTODO))
        */
        $db = \OC::$server->getDatabaseConnection();
        $sql = 'select * from oc_calendarobjects';
        $result = $db->executeQuery($sql);

        $list = [];
        $notFound = [];

        foreach ($result->fetchAll() as $appointment) {
            // Consume iCal data and convert it to JSEvent objects for JMAP for Calendar
            $icalobj = new \ZCiCal($appointment['calendardata']);

            foreach ($icalobj->tree->child as $node) {
                // Differentiate if the iCal component is an event and only
                // then use it for transformation to JMAP CalendarEvent (i.e. ignore VTODO, etc.)
                if ($node->getName() == "VEVENT") {
                    $adapter = new JmapCalendarEventAdapter($node);

                    $jmapCalendarEvent = new CalendarEvent();
                    $jmapCalendarEvent->setCalendarId($appointment['calendarid']);
                    $jmapCalendarEvent->setStart($adapter->getDTStart());
                    $jmapCalendarEvent->setDuration($adapter->getDuration());
                    $jmapCalendarEvent->setStatus($adapter->getStatus());
                    $jmapCalendarEvent->setType("jsevent");
                    $jmapCalendarEvent->setUid($adapter->getUid());
                    $jmapCalendarEvent->setProdId($adapter->getProdId());
                    $jmapCalendarEvent->setCreated($adapter->getCreated());
                    $jmapCalendarEvent->setUpdated($adapter->getLastModified());
                    $jmapCalendarEvent->setSequence($adapter->getSequence());
                    $jmapCalendarEvent->setTitle($adapter->getSummary());
                    $jmapCalendarEvent->setDescription($adapter->getDescription());
                    $jmapCalendarEvent->setLocations($adapter->getLocation());
                    $jmapCalendarEvent->setKeywords($adapter->getCategories());
                    $jmapCalendarEvent->setRecurrenceRule($adapter->getRRule());
                    $jmapCalendarEvent->setRecurrenceOverrides($adapter->getExDate());
                    $jmapCalendarEvent->setPriority($adapter->getPriority());
                    $jmapCalendarEvent->setPrivacy($adapter->getClass());
                    $jmapCalendarEvent->setTimeZone($adapter->getTimeZone());

                    array_push($list, $jmapCalendarEvent);
                }
            }


            //array_push($list, $icalobj);
        }

        $args = array("state" => "1234", "list" => $list, "notFound" => $notFound, "accountId" => "blaaaa");
        $invocation = array("CalendarEvent/get", $args, "0");
        $res = array("methodResponses" => array($invocation), "sessionState" => "0");


        return $res;
    }
}
