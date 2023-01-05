<?php

namespace OCA\JMAP\Middleware;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Middleware;
use OpenXPort\Jmap\Core\ErrorHandler;

class ErrorResponseMiddleware extends Middleware
{
    /**
     * This is being run when either the beforeController method or the
     * controller method itself is throwing an exception.
     *
     * @param Controller $controller the controller that is being called
     * @param string $methodName the name of the method that will be called on
     *                           the controller
     * @param \Exception $exception the thrown exception
     * @throws \Exception the passed in exception if it can't handle it
     * @return Response a Response object in case that the exception was handled
     * @since 6.0.0
     */
    public function afterException($controller, $methodName, \Exception $exception)
    {
        $description = "EXCEPTION " . $exception->getCode() . ":" .
            " - Message " . $exception->getMessage() .
            " - File " . $exception->getFile() .
            " - Line " . $exception->getLine();
        $args = array("type" => "serverFail", "description" => $description);

        return new JSONResponse(ErrorHandler::buildMethodResponse(
            0,  // TODO support methodCallId somehow in the future
            $args
        ), Http::STATUS_INTERNAL_SERVER_ERROR);
    }
}
