<?php

namespace OCA\JMAP\Tests\Unit\Controller;

use PHPUnit\Framework\TestCase;
use Sabre\CardDAV\Plugin;
use OCP\AppFramework\Http\DataDisplayResponse;
use OCA\JMAP\Controller\JmapController;

class JmapControllerTest extends TestCase
{
    private $controller;
    private $userId = 'john';

    private function init(): void
    {
        $request = $this->getMockBuilder('OCP\IRequest')->getMock();
        $davBackend = $this->getMockBuilder('OCA\DAV\CardDAV\CardDavBackend')->disableOriginalConstructor()->getMock();
        $davBackend->method('createCard')->willReturn('bla');
        $davBackend->method('createCard')->willReturn('bla');
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
        $davBackend->method('getUsersOwnAddressBooks')->willReturn($addressbooks);

        $this->controller = new JmapController(
            'jmap',
            $request,
            $davBackend,
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

        $this->init();

        $result = $this->controller->session();
        $this->assertTrue($result instanceof DataDisplayResponse);
    }

    public function testCardGetRequest(): void
    {
        $_SERVER['REQUEST_METHOD'] = "POST";

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
}
