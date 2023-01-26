<?php

namespace OCA\JMAP\Tests\Unit\Controller;

use PHPUnit\Framework\TestCase;
use OCP\AppFramework\Http\DataResponse;
use OCA\JMAP\Controller\JmapController;

class JmapControllerTest extends TestCase
{
    private $controller;
    private $userId = 'john';

    private function init(): void
    {
        $request = $this->getMockBuilder('OCP\IRequest')->getMock();

        $this->controller = new JmapController(
            'jmap',
            $request,
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
        $this->assertTrue($result == "");
    }

    public function testCardRequest(): void
    {
        $_SERVER['REQUEST_METHOD'] = "POST";

        $this->init();

        $using = array("https://www.audriga.eu/jmap/jscontact/");
        $methodCalls = array(
            array("Card/get", array( "accountId" => "john"), "0")
        );

        $result = $this->controller->request($using, $methodCalls);
        $this->assertTrue($result == "");
    }
}
