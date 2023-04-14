<?php

namespace OCA\JMAP\Tests\Unit\Controller;

use OC\Group\Manager;
use OCA\JMAP\Controller\JmapController;
use OCP\AppFramework\Http\DataDisplayResponse;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use PHPUnit\Framework\TestCase;
use Sabre\CardDAV\Plugin;

class JmapControllerTest extends TestCase
{
    private $controller;
    private $userId = 'john';
    private $user;
    private $userSession;
    private $userManager;
    private $groupManager;

    private function initNormalAuth(): void
    {
        $this->user = $this->createMock(IUser::class);
        $this->userSession = $this->createMock(IUserSession::class);
        $this->userSession
             ->method('getUser')
             ->willReturn($this->user);
        $this->groupManager = $this->createMock(Manager::class);
    }

    private function initAdminAuth(): void
    {
        $this->user = $this->createMock(IUser::class);
        $this->userSession = $this->createMock(IUserSession::class);
        $adminUser = $this->createMock(IUser::class);
        $adminUser->expects($this->any())
                  ->method('getUID')
                  ->willReturn('adminUser');
        $this->userSession
             ->method('getUser')
             ->willReturn($adminUser);
        $this->groupManager = $this->createMock(Manager::class);
        $this->groupManager->expects($this->once())
                           ->method('getUserGroupIds')
                           ->with($adminUser)
                           ->willReturn(['admin']);
    }

    private function init(): void
    {
        $request = $this->getMockBuilder('OCP\IRequest')->getMock();

        $this->user->expects($this->any())
             ->method('getUID')
             ->willReturn('john');
        $this->userManager = $this->createMock(IUserManager::class);

        $cardDavBackend = $this->getMockBuilder('OCA\DAV\CardDAV\CardDavBackend')->disableOriginalConstructor()->getMock();
        $cardDavBackend->method('createCard')->willReturn('bla');
        $cardDavBackend->method('createCard')->willReturn('bla');
        $addressbooks = [
            [
                'id' => 1,
                'uri' => 'mocked-contacts',
                'principaluri' => 'principals/users/john',
                '{DAV:}displayname' => 'Mocked C0N74C75',
                '{' . Plugin::NS_CARDDAV . '}addressbook-description' => "Great address book",
                '{http://calendarserver.org/ns/}getctag' => '1',
                '{http://sabredav.org/ns}sync-token' => '0',
                '{' . \OCA\DAV\DAV\Sharing\Plugin::NS_OWNCLOUD . '}owner-principal' => 'principals/users/john',
            ]
        ];
        $cardDavBackend->method('getUsersOwnAddressBooks')->willReturn($addressbooks);

	    $calDavBackend = $this->getMockBuilder('OCA\DAV\CalDAV\CalDavBackend')->disableOriginalConstructor()->getMock();
        $calDavBackend->method("createCalendarObject")->willReturn("bla");

        $calendar = [
            [
            'id' => "c1",
            'uri' => 'mocked-calendars',
            'principaluri' => 'principals/users/john',
            '{http://calendarserver.org/ns/}getctag' => 'http://sabre.io/ns/sync/1001',
            '{http://sabredav.org/ns}sync-token' => '1001',
            '{urn:ietf:params:xml:ns:caldav}supported-calendar-component-set' => 'VEVENT',
            '{urn:ietf:params:xml:ns:caldav}schedule-calendar-transp' => 'opaque',
            '{' . \OCA\DAV\DAV\Sharing\Plugin::NS_OWNCLOUD . '}owner-principal' => 'principals/users/john',
            ] 
        ];

        $calDavBackend->method("getUsersOwnCalendars")->willReturn($calendar);
        $calDavBackend->method("getCalendarObject")->willReturn(["bla"]);

        $this->controller = new JmapController(
            'jmap',
            $request,
            $this->userManager,
            $this->groupManager,
            $this->userSession,
            $cardDavBackend,
	        $calDavBackend,
            $this->userId
        );
    }

    public function setUp(): void
    {
        $_SERVER['HTTP_HOST'] = "speida-meh.io";
        $_SERVER['HTTPS'] = 'on';
        $_SERVER['REQUEST_URI'] = "/index.php/apps/jmap/jmap";
        $_SERVER['REMOTE_ADDR'] = "10.0.2.100";
        $_SERVER['REQUEST_TIME'] = 1674661058;
        $_SERVER['REMOTE_PORT'] = 36440;
        $_SERVER['PHP_AUTH_USER'] = "john";
    }

    public function testSession(): void
    {
        $_SERVER['REQUEST_METHOD'] = "GET";

        $this->initNormalAuth();
        $this->init();

        $result = $this->controller->session();
        $this->assertTrue($result instanceof DataDisplayResponse);
    }

    public function testCardGetRequest(): void
    {
        $_SERVER['REQUEST_METHOD'] = "POST";

        $this->initNormalAuth();
        $this->init();

        $using = array("https://www.audriga.eu/jmap/jscontact/");
        $methodCalls = array(
            array("Card/get", array( "accountId" => "john"), "0")
        );

        $result = $this->controller->request($using, $methodCalls);
        $this->assertTrue($result instanceof DataDisplayResponse);

        $output = $this->getActualOutput();
        $out_json = json_decode($output, true);
        $this->assertArrayHasKey("methodResponses", $out_json);
        $this->assertIsArray($out_json["methodResponses"]);
        $this->assertIsArray($out_json["methodResponses"][0]);
        $this->assertEquals("Card/get", $out_json["methodResponses"][0][0]);
    }

    public function testCardSetCreateRequest(): void
    {
        $_SERVER['REQUEST_METHOD'] = "POST";

        $this->initNormalAuth();
        $this->init();

        $using = array("https://www.audriga.eu/jmap/jscontact/");
        $create = ["asd" => ["@type" => "Card", "@version" => "1.0", "fullName" => "Testi"]];
        $methodCalls = [
            ["Card/set", [
                "accountId" => "john",
                "create" => $create
            ], "0"]
        ];

        $result = $this->controller->request($using, $methodCalls);
        $this->assertTrue($result instanceof DataDisplayResponse);
    }

    public function testCardSetDestroyRequest(): void
    {
        $_SERVER['REQUEST_METHOD'] = "POST";

        $this->initNormalAuth();
        $this->init();

        $using = array("https://www.audriga.eu/jmap/jscontact/");
        $destroy = ["1#lol"];
        $methodCalls = [
            ["Card/set", [
                "accountId" => "john",
                "destroy" => $destroy
            ], "0"]
        ];

        $result = $this->controller->request($using, $methodCalls);
        $this->assertTrue($result instanceof DataDisplayResponse);
    }

    public function testAddressBookGetRequest(): void
    {
        $_SERVER['REQUEST_METHOD'] = "POST";

        $this->initNormalAuth();
        $this->init();

        $using = array("https://www.audriga.eu/jmap/jscontact/");
        $methodCalls = array(
            array("AddressBook/get", array( "accountId" => "john"), "0")
        );

        $result = $this->controller->request($using, $methodCalls);
        $this->assertTrue($result instanceof DataDisplayResponse);

        $output = $this->getActualOutput();
        $out_json = json_decode($output, true);
        $this->assertArrayHasKey("methodResponses", $out_json);
        $this->assertIsArray($out_json["methodResponses"]);
        $this->assertIsArray($out_json["methodResponses"][0]);
        $this->assertEquals("AddressBook/get", $out_json["methodResponses"][0][0]);
    }

    public function testAddressBookSetCreateRequest(): void
    {
        $_SERVER['REQUEST_METHOD'] = "POST";

        $this->initNormalAuth();
        $this->init();

        $using = array("https://www.audriga.eu/jmap/jscontact/");
        $create = ["asd" => ["name" => "testbook"]];
        $methodCalls = [
            ["AddressBook/set", [
                "accountId" => "john",
                "create" => $create
            ], "0"]
        ];

        $result = $this->controller->request($using, $methodCalls);
        $this->assertTrue($result instanceof DataDisplayResponse);

        $output = $this->getActualOutput();
        $out_json = json_decode($output, true);
        $this->assertArrayHasKey("methodResponses", $out_json);
        $this->assertIsArray($out_json["methodResponses"]);
        $this->assertIsArray($out_json["methodResponses"][0]);
        $this->assertEquals("AddressBook/set", $out_json["methodResponses"][0][0]);
    }

    public function testAddressBookSetDestroyRequest(): void
    {
        $_SERVER['REQUEST_METHOD'] = "POST";

        $this->initNormalAuth();
        $this->init();

        $using = array("https://www.audriga.eu/jmap/jscontact/");
        $destroy = ["4"];
        $methodCalls = [
            ["AddressBook/set", [
                "accountId" => "john",
                "destroy" => $destroy
            ], "0"]
        ];

        $result = $this->controller->request($using, $methodCalls);
        $this->assertTrue($result instanceof DataDisplayResponse);

        $output = $this->getActualOutput();
        $out_json = json_decode($output, true);
        $this->assertArrayHasKey("methodResponses", $out_json);
        $this->assertIsArray($out_json["methodResponses"]);
        $this->assertIsArray($out_json["methodResponses"][0]);
        $this->assertEquals("AddressBook/set", $out_json["methodResponses"][0][0]);
    }

    /* Greatly inspired from
     * https://github.com/nextcloud/impersonate/blob/master/tests/unit/Controller/SettingsControllerTest.php
     */
    public function testImpersonation(): void
    {
        $_SERVER['PHP_AUTH_USER'] = "adminUser*john";
        $_SERVER['REQUEST_METHOD'] = "GET";

        $this->initAdminAuth();
        $this->init();

        $this->userManager->expects($this->once())
                          ->method('get')
                          ->with('john')
                          ->willReturn($this->user);
        $this->groupManager->expects($this->once())
                           ->method('isAdmin')
                           ->with('adminUser')
                           ->willReturn(true);
        // Expect that the user is changed to "john"
        $this->userSession->expects($this->once())
                          ->method('setUser')
                          ->with($this->user);

        $result = $this->controller->session();
        $this->assertTrue($result instanceof DataDisplayResponse);

        $output = $this->getActualOutput();
        $out_json = json_decode($output, true);
        $this->assertStringContainsString("capabilities", $output);
        $this->assertArrayHasKey("username", $out_json);
        $this->assertNotEquals($_SERVER['PHP_AUTH_USER'], $out_json["username"]);
    }

    public function testCalendarEventGetRequest(): void
    {
        $_SERVER['REQUEST_METHOD'] = "POST";

        $this->initNormalAuth();
        $this->init();

        $using = array("urn:ietf:params:jmap:calendars");
        $methodCalls = array(
            array("CalendarEvent/get", array( "accountId" => "john"), "0")
        );

        $result = $this->controller->request($using, $methodCalls);
        $this->assertTrue($result instanceof DataDisplayResponse);

        $output = $this->getActualOutput();
        $out_json = json_decode($output, true);
        $this->assertArrayHasKey("methodResponses", $out_json);
        $this->assertIsArray($out_json["methodResponses"]);
        $this->assertIsArray($out_json["methodResponses"][0]);
        $this->assertEquals("CalendarEvent/get", $out_json["methodResponses"][0][0]);
    }

    public function testCalendarEventSetCreateRequest(): void
    {
        $_SERVER['REQUEST_METHOD'] = "POST";

        $this->initNormalAuth();
        $this->init();

        $using = array("urn:ietf:params:jmap:calendars");
        $create = ["1" => ["@type" => "Event", "title" => "Testi", "calendarId" => "c1"]];
        $methodCalls = [
            ["CalendarEvent/set", [
                "accountId" => "john",
                "create" => $create
            ], "0"]
        ];

        $result = $this->controller->request($using, $methodCalls);
        $this->assertTrue($result instanceof DataDisplayResponse);

        $output = $this->getActualOutput();
        $out_json = json_decode($output, true);
        $this->assertArrayHasKey("methodResponses", $out_json);
        $this->assertIsArray($out_json["methodResponses"]);
        $this->assertIsArray($out_json["methodResponses"][0]);
        $this->assertEquals("CalendarEvent/set", $out_json["methodResponses"][0][0]);
        $this->assertNotEmpty($out_json["methodResponses"][0][1]["created"]);
    }

    public function testCalendarEventSetDestroyRequest(): void
    {
        $_SERVER['REQUEST_METHOD'] = "POST";

        $this->initNormalAuth();
        $this->init();

        $using = array("urn:ietf:params:jmap:calendars");
        $destroy = ["1#lol"];
        $methodCalls = [
            ["CalendarEvent/set", [
                "accountId" => "john",
                "destroy" => $destroy
            ], "0"]
        ];

        $result = $this->controller->request($using, $methodCalls);
        $this->assertTrue($result instanceof DataDisplayResponse);
        
        $output = $this->getActualOutput();
        $out_json = json_decode($output, true);
        $this->assertArrayHasKey("methodResponses", $out_json);
        $this->assertIsArray($out_json["methodResponses"]);
        $this->assertIsArray($out_json["methodResponses"][0]);
        $this->assertEquals("CalendarEvent/set", $out_json["methodResponses"][0][0]);
        $this->assertNotEmpty($out_json["methodResponses"][0][1]["destroyed"]);
    }
}
