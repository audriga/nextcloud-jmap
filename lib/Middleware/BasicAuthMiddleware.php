<?php

namespace OCA\JMAP\Middleware;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Middleware;
use OCP\IUserSession;
use OCP\IUserManager;

class BasicAuthMiddleware extends Middleware
{
    private $userSession;
    private $userManager;

    public function __construct(IUserSession $userSession, IUserManager $userManager)
    {
        $this->userSession = $userSession;
        $this->userManager = $userManager;
    }

    public function beforeController($controller, $methodName)
    {
        // Only apply to JMAP controller
        if (get_class($controller) !== 'OCA\JMAP\Controller\JmapController') {
            return;
        }

        // Check if already authenticated
        if ($this->userSession->getUser() !== null) {
            return;
        }

        // Check for basic auth
        if (isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW'])) {
            $authUser = $_SERVER['PHP_AUTH_USER'];
            $authPass = $_SERVER['PHP_AUTH_PW'];

            $user = $this->userManager->checkPassword($authUser, $authPass);
            if ($user !== false) {
                $this->userSession->setUser($user);
            }
        }
    }
}
