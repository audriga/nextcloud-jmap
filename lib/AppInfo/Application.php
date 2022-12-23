<?php

namespace OCA\JMAP\AppInfo;

use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;

class Application extends App implements IBootstrap
{
    public const APP_ID = 'jmap';

    public function __construct()
    {
        parent::__construct(self::APP_ID);
    }

    public function register(IRegistrationContext $context): void
    {
        // ... registration logic goes here ...
        // iCal lib
        require(__DIR__ . '/../../icalendar/zapcallib.php');
        // Register the composer autoloader for packages shipped by this app
        include_once __DIR__ . '/../../vendor/autoload.php';
    }

    public function boot(IBootContext $context): void
    {
        // No boot logic for now ...
    }
}
