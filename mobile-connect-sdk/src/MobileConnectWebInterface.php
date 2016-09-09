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

namespace MCSDK;

use MCSDK\Discovery\IDiscoveryService;
use MCSDK\MobileConnectInterfaceHelper;
use MCSDK\MobileConnectRequestOptions;
use MCSDK\Discovery\DiscoveryResponse;
use MCSDK\Authentication\IAuthenticationService;
use MCSDK\Identity\IdentityService;
use MCSDK\Identity\IIdentityService;
use MCSDK\Utils\MobileConnectResponseType;

class MobileConnectWebInterface
{
    private $_discovery;
    private $_authentication;
    private $_identity;
    private $_config;
    private $_cacheWithSessionId;

    /**
     * Initializes a new instance of the MobileConnectWebInterface class
     * @param IDiscoveryService $discovery Instance of IDiscovery concrete implementation
     * @param IAuthenticationService $authentication Instance of IAuthentication concrete implementation
     * @param IIdentityService $identity Instance of IIdentityService concrete implementation
     * @param MobileConnectConfig $config Configuration options
     */
    public function __construct(IDiscoveryService $discovery, IAuthenticationService $authentication, IIdentityService $identity, MobileConnectConfig $config) {
        $this->_discovery = $discovery;
        $this->_authentication = $authentication;
        $this->_identity = $identity;
        $this->_config = $config;
        $this->_cacheWithSessionId = $config->getCacheResponsesWithSessionId() && !empty($discovery->getCache());
    }

    /**
     * Attempt discovery using the supplied parameters. If msisdn, mcc and mnc are null the result will be operator selection,
     * otherwise valid parameters will result in a StartAuthorization status
     * @param $request Originating web request
     * @param string $msisdn MSISDN from user
     * @param string $mcc Mobile Country Code
     * @param string $mnc Mobile Network Code
     * @param bool $shouldProxyCookies If cookies from the original request should be sent onto the discovery service
     * @param MobileConnectRequestOptions $options Optional parameters
     * @return MobileConnectStatus object with required information for continuing the mobileconnect process
     */
    public function AttemptDiscovery($request, $msisdn, $mcc, $mnc, $shouldProxyCookies,
        MobileConnectRequestOptions $options) {

        $clientIp = empty($options->getClientIp()) ? $request->ip_address() : $options->getClientIp();
        $options->setClientIp($clientIp);
        $cookies = $shouldProxyCookies ? $request->cookie() : null;
        $response = MobileConnectInterfaceHelper::AttemptDiscovery($this->_discovery, $msisdn, $mcc, $mnc, $this->_config, $options, $cookies);
        return $this->cacheIfRequired($response);
    }

    /**
     * Attempt discovery using the values returned from the operator selection redirect
     * @param $request Originating web request
     * @param string $redirectedUrl Uri redirected to by the completion of the operator selection UI
     * @return MobileConnectStatus object with required information for continuing the mobileconnect process
     */
    public function AttemptDiscoveryAfterOperatorSelection($request, $redirectedUrl) {
        $response = MobileConnectInterfaceHelper::AttemptDiscoveryAfterOperatorSelection($this->_discovery, $redirectedUrl, $this->_config);
        return $this->cacheIfRequired($response);
    }

    /**
     * Creates an authorization url with parameters to begin the authetication process
     * @param $request Originating web request
     * @param string $sdkSession SDKSession id used to fetch the discovery response with additional parameters that are required to generate the url
     * @param string $encryptedMSISDN Encrypted MSISDN/Subscriber Id returned from the Discovery process
     * @param string $state Unique string to be used to prevent Cross Site Forgery Request attacks during request token process
     * (defaults to guid if not supplied, value will be returned in MobileConnectStatus object)
     * @param string $nonce Unique string to be used to prevent replay attacks during request token process
     * (defaults to guid if not supplied, value will be returned in MobileConnectStatus object)
     * @param MobileConnectRequestOptions $options Optional parameters
     * @return MobileConnectStatus object with required information for continuing the mobileconnect process
     */
    public function StartAuthentication($sdkSession, $encryptedMSISDN, $state, $nonce, MobileConnectRequestOptions $options) {
        $discoveryResponse = $this->getSessionFromCache($sdkSession);
        if (empty($discoveryResponse)) {
            return $this->getCacheError();
        }
        return $this->Authentication($discoveryResponse, $encryptedMSISDN, $state, $nonce, $options);
    }

    /**
     * Handles continuation of the process following a completed redirect, the request token url must be provided if it has been
     * returned by the discovery process. Only the request and redirectedUrl are required, however if the redirect being handled
     * is the result of calling the Authorization URL then the remaining parameters are required.
     * @param $request Originating web request
     * @param string $redirectedUrl Url redirected to by the completion of the previous step
     * @param string $sdkSession id used to fetch the discovery response with additional parameters that are required to request a token
     * @param string $expectedState The state value returned from the StartAuthorization call should be passed here, it will be used to validate the authenticity of the authorization process
     * @param string $expectedNonce The nonce value returned from the StartAuthorization call should be passed here, it will be used to ensure the token was not requested using a replay attack
     * @return MobileConnectStatus object with required information for continuing the mobileconnect process
     */
    public function HandleUrlRedirect($redirectedUrl,  $sdkSession = null, $expectedState = null, $expectedNonce = null) {
        $discoveryResponse = $this->getSessionFromCache($sdkSession);

        if (empty($discoveryResponse) && (!empty($expectedNonce) || !empty($expectedState) || !empty($sdkSession))) {
            return $this->getCacheError();
        }
        $status = MobileConnectInterfaceHelper::HandleUrlRedirect($this->_discovery, $redirectedUrl, $expectedState, $expectedNonce, $this->_config, $this->_authentication, $discoveryResponse);
        return $this->cacheIfRequired($status);
    }

    /**
     * Creates an authorization url with parameters to begin the authetication process
     * @param $request Originating web request
     * @param DiscoveryResponse $discoveryResponse The response returned by the discovery process
     * @param string $encryptedMSISDN Encrypted MSISDN/Subscriber Id returned from the Discovery process
     * @param string @state Unique string to be used to prevent Cross Site Forgery Request attacks during request token process (defaults to guid if not supplied, value will be returned in MobileConnectStatus object)
     * @param string @nonce Unique string to be used to prevent replay attacks during request token process (defaults to guid if not supplied, value will be returned in MobileConnectStatus object)
     * @param MobileConnectRequestOptions $options Optional parameters
     * @return MobileConnectStatus object with required information for continuing the mobileconnect process
     */
    public function Authentication(DiscoveryResponse $discoveryResponse, $encryptedMSISDN, $state, $nonce, MobileConnectRequestOptions $options) {
        $state = empty($state) ? $this->generateUniqueString() : $state;
        $nonce = empty($nonce) ? $this->generateUniqueString() : $nonce;
        return MobileConnectInterfaceHelper::StartAuthentication($this->_authentication, $discoveryResponse, $encryptedMSISDN, $state, $nonce, $this->_config, $options);
    }

    public function RequestTokenByDiscoveryResponse(DiscoveryResponse $discoveryResponse, $redirectedUrl, $expectedState, $expectedNonce) {
        $response = MobileConnectInterfaceHelper::RequestToken($this->_authentication, $discoveryResponse, $redirectedUrl, $expectedState, $expectedNonce, $this->_config);
        return $response;
    }

    public function RequestToken($sdkSession, $redirectedUrl, $expectedState, $expectedNonce)
    {
        $discoveryResponse = $this->GetSessionFromCache($sdkSession);

        if (empty($discoveryResponse))
        {
            return $this->GetCacheError();
        }

        return $this->RequestTokenByDiscoveryResponse($discoveryResponse, $redirectedUrl, $expectedState, $expectedNonce);
    }

    public function RequestUserInfoByDiscoveryResponse(DiscoveryResponse $discoveryResponse, $accessToken, MobileConnectRequestOptions $options)
    {
        return MobileConnectInterfaceHelper::RequestUserInfo($this->_identity, $discoveryResponse, $accessToken, $this->_config, $options);
    }


    public function RequestUserInfo($sdkSession, $accessToken, MobileConnectRequestOptions $options)
    {
        $discoveryResponse = $this->GetSessionFromCache($sdkSession);

        if (empty($discoveryResponse))
        {
            return GetCacheError();
        }

        return $this->RequestUserInfoByDiscoveryResponse($discoveryResponse, $accessToken, $options);
    }

    public function RequestIdentityByDiscoveryResponse(DiscoveryResponse $discoveryResponse, $accessToken, MobileConnectRequestOptions $options)
    {
        return MobileConnectInterfaceHelper::RequestIdentity($this->_identity, $discoveryResponse, $accessToken, $this->_config, $options);
    }

    public function RequestIdentity($sdkSession, $accessToken, MobileConnectRequestOptions $options)
    {
        $discoveryResponse = $this->GetSessionFromCache($sdkSession);

        if (empty($discoveryResponse))
        {
            return $this->GetCacheError();
        }

        return $this->RequestIdentityByDiscoveryResponse($discoveryResponse, $accessToken, $options);
    }

    private function generateUniqueString() {
        $temp = trim(strtolower(com_create_guid()), '{}');
        return str_replace('-', '', $temp);
    }

    private function cacheIfRequired(MobileConnectStatus $status) {
        if (empty($this->_cacheWithSessionId) || ($status->getResponseType() != MobileConnectResponseType::StartAuthentication) || empty($status->getDiscoveryResponse())) {
            return $status;
        }
        $sessionId = $this->generateUniqueString();
        $this->_discovery->getCache()->addKey($sessionId, $status->getDiscoveryResponse());
        $status->setSDKSession($sessionId);
        return $status;
    }

    private function getSessionFromCache($sessionId) {
        if (empty($this->_cacheWithSessionId) || empty($sessionId)) {
            return null;
        }
        return $this->_discovery->getCache()->getKey($sessionId);
    }

    private function getCacheError() {
        if (empty($this->_cacheWithSessionId)) {
            return MobileConnectStatus::Error("cache_disabled", "cache is not enabled for session id caching of discovery responses", null);
        }
        return MobileConnectStatus::Error("sdksession_not_found", "session not found or expired, please try again", null);
    }
}
