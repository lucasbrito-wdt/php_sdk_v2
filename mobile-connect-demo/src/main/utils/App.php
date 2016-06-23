<?php

/**
 *                          SOFTWARE USE PERMISSION
 *
 *  By downloading and accessing this software and associated documentation
 *  files ("Software") you are granted the unrestricted right to deal in the
 *  Software, including, without limitation the right to use, copy, modify,
 *  publish, sublicense and grant such rights to third parties, subject to the
 *  following conditions:
 *
 *  The following copyright notice and this permission notice shall be included
 *  in all copies, modifications or substantial portions of this Software:
 *  Copyright © 2016 GSM Association.
 *
 *  THE SOFTWARE IS PROVIDED "AS IS," WITHOUT WARRANTY OF ANY KIND, INCLUDING
 *  BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A
 *  PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR
 *  COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
 *  WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR
 *  IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 *  SOFTWARE. YOU AGREE TO INDEMNIFY AND HOLD HARMLESS THE AUTHORS AND COPYRIGHT
 *  HOLDERS FROM AND AGAINST ANY SUCH LIABILITY.
 */

namespace MCAPP\main;

use MCSDK\cache\DiscoveryCacheImpl;
use MCSDK\discovery\DiscoveryResponse;
use MCSDK\helpers\MobileConnectConfig;
use MCSDK\helpers\MobileConnectInterface;
use MCSDK\helpers\MobileConnectStatus;
use MCSDK\impl\Factory;
use MCSDK\utils\URIBuilder;
use Zend\Log\Logger;
use Zend\Log\Writer\Stream;

session_start();

/**
 * Mobile Connect demonstration application.
 *
 * This is hosted as a spring boot application, but spring boot is not required to use the Mobile Connect SDK.
 *
 */
class App
{

    const ERROR_ATTRIBUTE_NAME = "error";
    const ERROR_DESCRIPTION_ATTRIBUTE_NAME = "error_description";
    const ERROR_PAGE = "error-page.php";
    const AUTHORIZED_PAGE = "authorisation-finished.php";
    const START_AUTHORIZATION_PAGE = "request-authorisation.php";
    const START_DISCOVERY_PAGE = "request_discovery_page";
    const SESSION_KEY = "demo:key";

    private $oidc;
    private $discovery;
    private $logger;

    /**
     * App constructor.
     */
    public function __construct()
    {
        date_default_timezone_set("Europe/London");

        $this->oidc = Factory::getOIDC();

        $cache = new DiscoveryCacheImpl();
        $this->discovery = Factory::getDiscovery($cache);

        $this->logger = new Logger();
        $writer = new Stream('./PHP_Log');

        $this->logger->addWriter($writer);
    }

    /**
     * Gets the mobile connect config used for rest interactions with mobile connect
     *
     * @return MobileConnectConfig|null the mobile connect config
     */
    private function getMobileConnectConfig()
    {
        // The Mobile Connect Interface methods expects a configuration object.
        // This can be unique per call or shared between calls as required.
        // Most of the values in the configuration object are optional.
        $mobileConnectConfig = (isset($_SESSION[self::SESSION_KEY])) ? $_SESSION[self::SESSION_KEY] : null;

        if (is_null($mobileConnectConfig)) {
            $mobileConnectConfig = new MobileConnectConfig();

            // Registered application client id
            $mobileConnectConfig->setClientId("1b86fe60-12f0-4f95-b26d-d8199b2a858b");

            // Registered application client secret
            $mobileConnectConfig->setClientSecret("2dd72203-2c75-4381-8cd6-f45355e4abd6");

            // Registered application url
            $mobileConnectConfig->setApplicationURL("http://mobile.connect.demo/authorisation-redirect.php");

            // URL of the Mobile Connect Discovery End Point
            $mobileConnectConfig->setDiscoveryURL("http://discovery.sandbox2.mobileconnect.io/v2/discovery");

            // URL to inform the Discovery End Point to redirect to, this should route to the "/discovery_redirect" handler below
            $mobileConnectConfig->setDiscoveryRedirectURL("http://mobile.connect.demo/discovery-redirect.php");

            // Authorization State would typically set to a unique value
            $mobileConnectConfig->setAuthorizationState(MobileConnectInterface::generateUniqueString("state_"));

            // Authorization Nonce would typically set to a unique value
            $mobileConnectConfig->setAuthorizationNonce(MobileConnectInterface::generateUniqueString("nonce_"));

            $_SESSION[self::SESSION_KEY] = $mobileConnectConfig;
        }

        return $mobileConnectConfig;
    }

    /**
     * This is the endpoint to initiate mobile connect.
     *
     * The client javascript makes a JSON call to this method to initiate mobile connect authorization.
     * Authorization is carried out per operator, so the first step is to determine the operator.
     *
     * @return string JSON object describing what to do next either error, display the operator selection page or the operator authorization page
     */
    public function startDiscovery()
    {

        $config = $this->getMobileConnectConfig();

        // This wraps the SDK to determine what the next step in the authorization process is: operator discovery or authorization with an operator.
        $mobileConnectStatus = MobileConnectInterface::callMobileConnectForStartDiscovery($this->discovery, $config);

        if ($mobileConnectStatus->isOperatorSelection()) {
            $this->logger->log(Logger::DEBUG, "The operator is unknown, redirect to the operator selection url: " . $mobileConnectStatus->getUrl());
        } else if ($mobileConnectStatus->isStartAuthorization()) {
            $this->logger->log(Logger::DEBUG, "The operator has been identified without requiring operator selection");

            $discoveryResponse = $mobileConnectStatus->getDiscoveryResponse();
            $this->logDiscoveryResponse($discoveryResponse);
        } else {
            // An error occurred, the error, description and nested exception (optional) are available.
            $this->logError($mobileConnectStatus);
        }

        return $mobileConnectStatus->getResponseJson();
    }

    /**
     * This is called by the redirect from the operator selection service.
     *
     * The identified operator is encoded in the query string.
     *
     * It responds with either:
     * <ul>
     * <li>an error
     * <li>a request to display the operator selection again
     * <li>a request to move onto authorization.
     * </ul>
     *
     * It is assumed that this is redirected to from the operator selection popup so the return is a web page that contains
     * javascript that calls the parent page to continue the authorization process (either redisplay the operator selection
     * pop up or start authorization with the identified operator).
     *
     * @return null|string if returned is a reference to a web page for JS to use
     */
    public function discoveryRedirect()
    {
        $config = $this->getMobileConnectConfig();

        $mobileConnectStatus = MobileConnectInterface::callMobileConnectOnDiscoveryRedirect($this->discovery, $config);

        if ($mobileConnectStatus->isError()) {
            // An error occurred, the error, description and nested exception (optional) are available.
            $this->logError($mobileConnectStatus);
        } else if ($mobileConnectStatus->isStartDiscovery()) {
            $this->logger->log(Logger::DEBUG, "The operator could not be identified, need to restart the discovery process.");
        } else if ($mobileConnectStatus->isStartAuthorization()) {
            $this->logger->log(Logger::DEBUG, "The operator has been identified and the authorization process can begin");

            $this->logDiscoveryResponse($mobileConnectStatus->getDiscoveryResponse());
        }

        return $this->toPageDescription($mobileConnectStatus);
    }

    /**
     * This is called by the client javascript to initiate authorization with an operator.
     *
     * This is typically called after the operator has been determined. The identified operator's discovery response is expected to
     * be stored in the session.
     *
     * It will return a json object that contains an error, a request to initiate operator discovery or a url
     * to redirect to start authorization with the identified operator.
     *
     * @return string JSON object.
     */
    public function startAuthorization()
    {
        $config = $this->getMobileConnectConfig();

        $mobileConnectStatus = MobileConnectInterface::callMobileConnectForStartAuthorization($this->oidc, $config);

        if ($mobileConnectStatus->isError()) {
            $this->logger->log(Logger::DEBUG, "Failed starting the authorization process");

            $this->logError($mobileConnectStatus);
        } else if ($mobileConnectStatus->isStartDiscovery()) {
            $this->logger->log(Logger::DEBUG, "The operator could not be identified, need to restart the discovery process.");
        } else if ($mobileConnectStatus->isAuthorization()) {
            $this->logger->log(Logger::DEBUG, "The operator has been identified and the authorization process can start");
            $this->logger->log(Logger::DEBUG, "URL is: " . $mobileConnectStatus->getUrl());

            $this->logDiscoveryResponse($mobileConnectStatus->getDiscoveryResponse());
        }

        return $mobileConnectStatus->getResponseJson();
    }

    /**
     * This is called by the redirect from the operator authentication function.
     * <p>
     * This contains information that allows the SDK to obtain an authorization token (PCR) directly from the operator.
     * <p>
     * The response is either a successful authorization, an error or a request to identify the operator.
     *
     * @return null|string if returned is a reference to a web page for JS to use
     */
    public function authorizationRedirect()
    {
        $config = $this->getMobileConnectConfig();

        $mobileConnectStatus = MobileConnectInterface::callMobileConnectOnAuthorizationRedirect($this->oidc, $config);

        if ($mobileConnectStatus->isError()) {
            $this->logger->log(Logger::DEBUG, "Authorization has failed");

            $this->logError($mobileConnectStatus);
        } else if ($mobileConnectStatus->isStartDiscovery()) {
            $this->logger->log(Logger::DEBUG, "The operator could not be identified, need to restart the discovery process.");
        } else if ($mobileConnectStatus->isComplete()) {
            $this->logger->log(Logger::DEBUG, "Authorization has completed successfully");
            $this->logger->log(Logger::DEBUG, "PCR is " . $mobileConnectStatus->getRequestTokenResponse()->getResponseData()->getParsedIdToken()->get_pcr());

            $this->logAuthorized($mobileConnectStatus);
        }

        return $this->toPageDescription($mobileConnectStatus);
    }

    /**
     * Handles any mobile connect rest api errors
     *
     * @param \stdClass $request the request attempted containing an error
     */
    public function mobileConnectError($request)
    {
        $uriBuilder = new URIBuilder(self::ERROR_PAGE);
        $uriBuilder->addParameter(self::ERROR_ATTRIBUTE_NAME, $request->{self::ERROR_ATTRIBUTE_NAME});
        $uriBuilder->addParameter(self::ERROR_DESCRIPTION_ATTRIBUTE_NAME, $request->{self::ERROR_DESCRIPTION_ATTRIBUTE_NAME});
        header('Location: ' . urldecode($uriBuilder->build()));
    }

    /**
     * Handles any mobile connect rest api errors
     *
     * @param \Exception $ex the exception thrown by the rest client
     */
    public function handleException(\Exception $ex)
    {
        $this->logger->log(Logger::ERR, "Uncaught exception", $ex);

        $uriBuilder = new URIBuilder(self::ERROR_PAGE);
        $uriBuilder->addParameter(self::ERROR_ATTRIBUTE_NAME, "internal error");
        header('Location: ' . urldecode($uriBuilder->build()));
    }

    /**
     * Map/Redirect the application to a PHP page.
     *
     * @param MobileConnectStatus $status The MobileConnectStatus to map.
     * @return string|null the name of the PHP page to redirect the browser to
     */
    private function toPageDescription(MobileConnectStatus $status)
    {
        if ($status->isComplete()) {
            $idToken = $status->getRequestTokenResponse()->getResponseData()->getParsedIdToken();

            header('Location: /' . self::AUTHORIZED_PAGE . '?idToken=' . base64_encode(serialize($idToken)));
        } else if ($status->isStartDiscovery()) {
            return self::START_DISCOVERY_PAGE;
        } else if ($status->isStartAuthorization()) {
            header('Location: /' . self::START_AUTHORIZATION_PAGE);
        } else {
            $uriBuilder = new URIBuilder(self::ERROR_PAGE);
            $uriBuilder->addParameter(self::ERROR_ATTRIBUTE_NAME, $status->getError());
            $uriBuilder->addParameter(self::ERROR_DESCRIPTION_ATTRIBUTE_NAME, $status->getDescription());
            header('Location: ' . urldecode($uriBuilder->build()));
        }

        return null;
    }

    /**
     * @param DiscoveryResponse $response the response from the rest client
     */
    private function logDiscoveryResponse(DiscoveryResponse $response)
    {
        $this->logger->log(Logger::DEBUG, "Is the DiscoveryResponse from the cache: " . $response->isCached());
        $this->logger->log(Logger::DEBUG, "Discovery response code: " . $response->getResponseCode());

        // The Discovery Response contains the response headers (if not from cache).
        if (!is_null($response->getHeaders())) {
            foreach ($response->getHeaders() as $key => $value) {
                $this->logger->log(Logger::DEBUG, $key . ": " . print_r($value, true));
            }
        }

        // The Discovery Response contains the discovery JSON object
        $discoveryJson = $response->getResponseData();
        $this->logger->log(Logger::DEBUG, "Serving operator is: " . $discoveryJson->response->serving_operator);
    }

    /**
     * Log the authorised response
     *
     * @param MobileConnectStatus $status the status after authorisation response handling
     */
    private function logAuthorized(MobileConnectStatus $status)
    {
        $parsedAuthorizationResponse = $status->getParsedAuthorizationResponse();
        if (!is_null($parsedAuthorizationResponse)) {
            $this->logger->log(Logger::DEBUG, "Code: " . $parsedAuthorizationResponse->get_code());
            $this->logger->log(Logger::DEBUG, "State: " . $parsedAuthorizationResponse->get_state());
        }

        $requestTokenResponse = $status->getRequestTokenResponse();
        if (!is_null($requestTokenResponse)) {
            $this->logger->log(Logger::DEBUG, "Response Code: " . $requestTokenResponse->getResponseCode());
            if (!is_null($requestTokenResponse->getHeaders())) {
                foreach ($requestTokenResponse->getHeaders() as $key => $value) {
                    $this->logger->log(Logger::DEBUG, $key . ": " . json_encode($value));
                }
            }
            if (!is_null($requestTokenResponse->getResponseData())) {
                $responseData = $requestTokenResponse->getResponseData();
                $this->logger->log(Logger::DEBUG, "Time Received: " . $responseData->getTimeReceived()->getTimestamp());
                if (!is_null($responseData->getParsedIdToken())) {
                    $parsedIdToken = $responseData->getParsedIdToken();
                    $this->logger->log(Logger::DEBUG, "Nonce: " . $parsedIdToken->get_nonce());
                    $this->logger->log(Logger::DEBUG, "PCR: " . $parsedIdToken->get_pcr());
                }
            }

        }
    }

    /**
     * Logs the error details during rest call failures
     *
     * @param MobileConnectStatus $status the status after error on rest call
     */
    private function logError(MobileConnectStatus $status)
    {
        $this->logger->log(Logger::ERR, "An error occurred: " . $status->getError() . ": " . $status->getDescription());
        $exception = $status->getException();
        $this->logger->log(Logger::ERR, "Exception was: " . $exception);

        if (!is_null($exception)) {
            $this->logger->log(Logger::ERR, $exception->getMessage());
            $this->logger->log(Logger::ERR, "URI: " . $exception->getUri());
            $this->logger->log(Logger::ERR, "Response code: " . $exception->getResponseCode());
            $this->logger->log(Logger::ERR, "Contents: " . $exception->getContents());
            if (!is_null($exception->getHeaders())) {
                foreach ($exception->getHeaders() as $key => $value) {
                    $this->logger->log(Logger::ERR, $key . ": " . json_encode($value));
                }
            }
        }
    }

}
