<?php

namespace OCA\JMAP\AppInfo;

use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCA\JMAP\Middleware\ErrorResponseMiddleware;

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

        $context->registerMiddleware(ErrorResponseMiddleware::class);
    }

    public function boot(IBootContext $context): void
    {
        $context->injectFn([$this, 'registerContactsManager']);
    }

    public function registerContactsManager(IContactsManager $cm, IAppContainer $container): void
    {
        $cm->register(function () use ($container, $cm): void {
            $user = \OC::$server->getUserSession()->getUser();
            if (!is_null($user)) {
                $this->setupContactsProvider($cm, $container, $user->getUID());
            } else {
                // This is prbably the admin case??
                // TODO verify that
                $this->setupSystemContactsProvider($cm, $container);
            }
        });
    }

    private function setupContactsProvider(
        IContactsManager $contactsManager,
        IAppContainer $container,
        string $userID
    ): void {
        /** @var ContactsManager $cm */
        $cm = $container->query(ContactsManager::class);
        $urlGenerator = $container->getServer()->getURLGenerator();
        $cm->setupContactsProvider($contactsManager, $userID, $urlGenerator);
    }

    private function setupSystemContactsProvider(
        IContactsManager $contactsManager,
        IAppContainer $container
    ): void {
        /** @var ContactsManager $cm */
        $cm = $container->query(ContactsManager::class);
        $urlGenerator = $container->getServer()->getURLGenerator();
        $cm->setupSystemContactsProvider($contactsManager, $urlGenerator);
    }
}
