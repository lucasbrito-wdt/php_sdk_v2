<?php

require_once(dirname(__FILE__) . '/../../../vendor/autoload.php');

use MCSDK\MobileConnectWebInterface;
use MCSDK\MobileConnectRequestOptions;
use MCSDK\Discovery\DiscoveryService;
use MCSDK\Discovery\IDiscoveryService;
use MCSDK\MobileConnectConfig;
use Zend\Http\Response;
use MCSDK\Web\ResponseConverter;
use MCSDK\MobileConnectStatus;
use MCSDK\Utils\JsonUtils;
use MCSDK\Authentication\AuthenticationService;
use MCSDK\Identity\IIdentityService;
use MCSDK\Identity\IdentityService;
use MCSDK\Cache\Cache;
use MCSDK\Utils\RestClient;
use MCSDK\Authentication\JWKeysetService;

class MobileConnectController extends CI_Controller {
    private $_mobileConnect;
    private $_operatorUrls;
    private $_apiVersion;
    private $_xRedirect = "APP";

    public function __construct() {
        parent::__construct();

        session_start();
        $cache = null;
        if (!isset($_SESSION['mc_session'])) {
            $cache = new Cache();
            $_SESSION['mc_session'] = $cache;
        } else {
            $cache = $_SESSION['mc_session'];
        }

        $discoveryService = new DiscoveryService(new RestClient(), $cache);
        $authentication = new AuthenticationService();
        $identity = new IdentityService(new RestClient());
        $jwks = new JWKeysetService(new RestClient(), $discoveryService->getCache());
        $config = new MobileConnectConfig();
        $string = file_get_contents(dirname(dirname(dirname(__FILE__))).DIRECTORY_SEPARATOR."data".DIRECTORY_SEPARATOR."defaultData.json");
        $json = json_decode($string, true);

        $config->setClientId($json["clientID"]);
        $config->setClientSecret($json["clientSecret"]);
        $config->setDiscoveryUrl($json["discoveryURL"]);
        $config->setRedirectUrl($json["redirectURL"]);
        $file_without_discovery = file_get_contents(dirname(dirname(dirname(__FILE__))). DIRECTORY_SEPARATOR."data".DIRECTORY_SEPARATOR."defaultDataWD.json");
        $json_without_discovery = json_decode($file_without_discovery, true);
        $this->_operatorUrls = new \MCSDK\Discovery\OperatorUrls();
        $providerMetadata = null;
        if(array_key_exists('providerMetadata',$json_without_discovery)) {
            $providerMetadata = $json_without_discovery['providerMetadata'];
            $this->_operatorUrls->setProviderMetadataUrl($providerMetadata);
        }
        else{
            $authorization = $json_without_discovery['authorizationURL'];
            $token = $json_without_discovery['tokenURL'];
            $userinfo = $json_without_discovery['userInfoURL'];
            $this->_operatorUrls->setAuthorizationUrl($authorization);
            $this->_operatorUrls->setRequestTokenUrl($token);
            $this->_operatorUrls->setUserInfoUrl($userinfo);
        }

        $this->_mobileConnect = new MobileConnectWebInterface($discoveryService, $authentication, $identity, $jwks, $config);
    }

    // Route "start_discovery"
    public function StartDiscovery($msisdn = "", $sourceIp = "") {
        //TODO: find better way to pass parameters to controller
        $msisdn = $this->input->get('msisdn', true);
        $sourceIp = $this->input->get('sourceIp', true);
        $options = new MobileConnectRequestOptions();
        if($sourceIp!=""){
            $options->setClientIp($sourceIp);
        }
        $options->getDiscoveryOptions()->setXRedirect($this->_xRedirect);
        $response = $this->_mobileConnect->AttemptDiscovery($this->input, $msisdn, null, null, true, $options);
        return $this->CreateResponse($response);
    }

    // Route start_manual_discovery
    public function StartManualDiscovery($subId = "", $clientId = "", $clientName = "", $clientSecret = "") {
        //TODO: find better way to pass parameters to controller
        $subId = $this->input->get('subId', true);
        $clientId = $this->input->get('clientId', true);
        $clientName = $this->input->get('clientName', true);
        $clientSecret = $this->input->get('clientSecret', true);

        $response = $this->_mobileConnect->makeDiscoveryWithoutCall($clientId, $clientSecret, $this->_operatorUrls, $clientName, $subId);

        return $this->CreateResponse($response);
    }

    //Route start_manual_discovery_no_metadata
    public function StartManualDiscoveryNoMetadata($subId = "", $clientId = "", $clientSecret = "") {
        //TODO: find better way to pass parameters to controller
        $subId = $this->input->get('subId', true);
        $clientId = $this->input->get('clientId', true);
        $clientSecret = $this->input->get('clientSecret', true);

        $response = $this->_mobileConnect->makeDiscoveryWithoutCall($clientId, $clientSecret, $this->_operatorUrls, "appName", $subId);

        return $this->CreateResponse($response);
    }

    //Route get_parameters
    public function GetParameters($clientId = "", $clientSecret = "", $discoveryUrl = "", $redirectUrl = "", $xRedirect = "", $apiVersion = ""){
        $clientId = $this->input->get('clientId', true);
        $clientSecret = $this->input->get('clientSecret', true);
        $discoveryUrl = $this->input->get('discoveryUrl', true);
        $redirectUrl = $this->input->get('redirectUrl', true);
        $xRedirect = $this->input->get('xRedirect', true);
        $apiVersion = $this->input->get('apiVersion', true);
        $cache = null;
        if (!isset($_SESSION['mc_session'])) {
            $cache = new Cache();
            $_SESSION['mc_session'] = $cache;
        } else {
            $cache = $_SESSION['mc_session'];
        }

        $discoveryService = new DiscoveryService(new RestClient(), $cache);
        $authentication = new AuthenticationService();
        $identity = new IdentityService(new RestClient());
        $jwks = new JWKeysetService(new RestClient(), $discoveryService->getCache());
        $this->_apiVersion = $apiVersion;
        $config = new MobileConnectConfig();
        $config->setClientId($clientId);
        $config->setClientSecret($clientSecret);
        $config->setDiscoveryUrl($discoveryUrl);
        $config->setRedirectUrl($redirectUrl);
        $this->_xRedirect = $xRedirect;
        $this->_mobileConnect = new MobileConnectWebInterface($discoveryService, $authentication, $identity, $jwks, $config);
    }

    //Route endpoints
    public function Endpoints($authURL = "", $tokenURL="", $userInfoURl="", $metadata = "", $discoveryURL = "", $redirectURL = ""){
        $this->_operatorUrls = new \MCSDK\Discovery\OperatorUrls();
        $providerMetadata = null;
        if($metadata!="") {
            $this->_operatorUrls->setProviderMetadataUrl($metadata);
        }
        else{
            $authorization = $authURL;
            $token = $tokenURL;
            $userinfo = $userInfoURl;
            $this->_operatorUrls->setAuthorizationUrl($authorization);
            $this->_operatorUrls->setRequestTokenUrl($token);
            $this->_operatorUrls->setUserInfoUrl($userinfo);
        }
    }

    // Route "start_authentication"
    public function StartAuthentication($sdkSession = null, $subscriberId = null, $scope = null) {
        //TODO: find better way to pass parameters to controller
        $sdkSession = $this->input->get('sdkSession', true);
        $subscriberId = $this->input->get('subscriberId', true);
        $scope = $this->input->get('scope', true);

        $options = new MobileConnectRequestOptions();
        $options->setScope($scope);
        $options->setContext("demo");
        $options->setBindingMessage("demo auth");
        $response = $this->_mobileConnect->StartAuthentication($sdkSession, $subscriberId, null, null, $options);

        return $this->CreateResponse($response);
    }

    // Route "start_authentication_r1"
    public function StartAuthenticationR1($sdkSession = null, $subscriberId = null, $scope = null) {
        //TODO: find better way to pass parameters to controller
        $sdkSession = $this->input->get('sdkSession', true);
        $subscriberId = $this->input->get('subscriberId', true);
        $scope = $this->input->get('scope', true);

        $options = new MobileConnectRequestOptions();
        $options->setScope($scope);
        $response = $this->_mobileConnect->StartAuthentication($sdkSession, $subscriberId, null, null, $options);

        return $this->CreateResponse($response);
    }

    // Route "headless_authentication"
    public function RequestHeadlessAuthentication($sdkSession = null, $subscriberId = null, $scope = null) {
        $sdkSession = $this->input->get('sdkSession', true);
        $subscriberId = $this->input->get('subscriberId', true);
        $scope = $this->input->get('scope', true);

        $options = new MobileConnectRequestOptions();
        $options->setScope($scope);
        $options->setContext("headless");
        $options->setBindingMessage("demo headless");
        $response = $this->_mobileConnect->RequestHeadlessAuthentication($sdkSession, $subscriberId, null, null, $options);
        return $this->CreateResponse($response);
    }

    // Route "user_info"
    public function RequestUserInfo($sdkSession = null, $accessToken = null) {
        $sdkSession = $this->input->get('sdkSession', true);
        $accessToken = $this->input->get('accessToken', true);

        $response = $this->_mobileConnect->RequestUserInfo($sdkSession, $accessToken, new MobileConnectRequestOptions());
        return $this->CreateResponse($response);
    }

    // Route "identity"
    public function RequestIdentity($sdkSession = null, $accessToken = null) {
        $sdkSession = $this->input->get('sdkSession', true);
        $accessToken = $this->input->get('accessToken', true);

        $response = $this->_mobileConnect->RequestIdentity($sdkSession, $accessToken, new MobileConnectRequestOptions());
        return $this->CreateResponse($response);
    }

    // Route ""
    public function HandleRedirect($sdkSession = null, $mcc_mnc = null, $code = null, $expectedState = null, $expectedNonce = null) {
        $sdkSession = $this->input->get('sdkSession', true);
        $expectedState = $this->input->get('expectedState', true);
        $expectedNonce = $this->input->get('expectedNonce', true);
        $requestUri = $this->input->server('REQUEST_URI');

        $response = $this->_mobileConnect->HandleUrlRedirect($requestUri, $sdkSession, $expectedState, $expectedNonce, new MobileConnectRequestOptions());
        $tmp = $this->CreateResponse($response);
        return $tmp;
    }

    public function RefreshToken($sdkSession = null, $refreshToken = null) {
        $sdkSession = $this->input->get('sdkSession', true);
        $refreshToken = $this->input->get('refreshToken', true);

        $response = $this->_mobileConnect->RefreshToken($refreshToken, $sdkSession);
        return $this->CreateResponse($response);
    }

    public function RevokeToken($sdkSession = null, $accessToken = null) {
        $sdkSession = $this->input->get('sdkSession', true);
        $accessToken = $this->input->get('accessToken', true);

        $response = $this->_mobileConnect->RevokeToken($accessToken, "access_token", $sdkSession);
        return $this->CreateResponse($response);
    }

    private function CreateResponse(MobileConnectStatus $status) {
        return $this->output
            ->set_content_type('application/json')
            ->set_status_header(200)
            ->set_output(JsonUtils::toJson(ResponseConverter::Convert($status)));
    }
}
