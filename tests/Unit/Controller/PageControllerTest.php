<?php

namespace OCA\JMAP\Tests\Unit\Controller;

use PHPUnit\Framework\TestCase;
use OCP\AppFramework\Http\DataResponse;
use OCA\JMAP\Controller\PageController;

class PageControllerTest extends TestCase
{
    private $controller;
    private $userId = 'john';

    public function setUp(): void
    {
        $request = $this->getMockBuilder('OCP\IRequest')->getMock();

        $this->controller = new PageController(
            'jmap',
            $request,
            $this->userId
        );
    }

    public function testIndex(): void
    {
        $result = $this->controller->index();

        $this->assertTrue($result instanceof DataResponse);
        $this->assertEquals('OpenXPort JMAP API for Nextcloud, powered by NGI DAPSI, is enabled.', $result->getData());
    }
}
