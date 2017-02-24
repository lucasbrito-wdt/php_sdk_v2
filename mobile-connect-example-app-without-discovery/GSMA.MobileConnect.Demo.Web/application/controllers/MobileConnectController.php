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
        $fileConfig = parse_ini_file(dirname(__FILE__) . '/../config/config.ini');
        $redirectUrl = $fileConfig['redirectUrl'];
        $this->_operatorUrls = new \MCSDK\Discovery\OperatorUrls();
        $providerMetadata = null;
        if(array_key_exists('providerMetadata',$fileConfig)) {
            $providerMetadata = $fileConfig['providerMetadata'];
            $this->_operatorUrls->setProviderMetadataUrl($providerMetadata);
        }
        else{
            $authorization = $fileConfig['authorization'];
            $token = $fileConfig['token'];
            $userinfo = $fileConfig['userinfo'];
            $tokenrevoke = $fileConfig['tokenrevoke'];
            $this->_operatorUrls->setAuthorizationUrl($authorization);
            $this->_operatorUrls->setRequestTokenUrl($token);
            $this->_operatorUrls->setUserInfoUrl($userinfo);
            $this->_operatorUrls->setRevokeTokenUrl($tokenrevoke);
        }

        $config = new MobileConnectConfig();
        $config->setClientId("");
        $config->setClientSecret("");
        $config->setDiscoveryUrl("");
        $config->setRedirectUrl($redirectUrl);
        $config->setCacheResponsesWithSessionId(true);
        $this->_mobileConnect = new MobileConnectWebInterface($discoveryService, $authentication, $identity, $jwks, $config);
    }

    // Route "start_manual_discovery"
    public function startDiscovery($subId = "", $clientId = "", $clientName = "", $clientSecret = "") {
        //TODO: find better way to pass parameters to controller
        $subId = $this->input->get('subId', true);
        $clientId = $this->input->get('clientId', true);
        $clientName = $this->input->get('clientName', true);
        $clientSecret = $this->input->get('clientSecret', true);

        $response = $this->_mobileConnect->makeDiscoveryWithoutCall($clientId, $clientSecret, $this->_operatorUrls, $clientName, $subId);

        return $this->CreateResponse($response);
    }

    // Route "start_authentication"
    public function startAuthentication($sdkSession = null, $subscriberId = null, $scope = null) {
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

    // Route "start_manual_discovery_no_metadata"
    public function startDiscoveryNoMetadata($subId = "", $clientId = "", $clientSecret = "") {
        $subId = $this->input->get('subId', true);
        $clientId = $this->input->get('clientId', true);
        $clientSecret = $this->input->get('clientSecret', true);

        $response = $this->_mobileConnect->makeDiscoveryWithoutCall($clientId, $clientSecret, $this->_operatorUrls, "PHP Demo" ,$subId);
        return $this->CreateResponse($response);
    }

    // Route "start_authentication_r1"
    public function startAuthenticationR1($sdkSession = null, $subscriberId = null, $scope = null) {
        $sdkSession = $this->input->get('sdkSession', true);
        $subscriberId = $this->input->get('subscriberId', true);
        $scope = $this->input->get('scope', true);

        $options = new MobileConnectRequestOptions();
        $options->setScope($scope);
        $response = $this->_mobileConnect->StartAuthentication($sdkSession, $subscriberId, null, null, $options);

        return $this->CreateResponse($response);
    }

    // Route ""
    public function HandleRedirect($code = null, $state = null, $expectedState = null, $expectedNonce = null, $sdkSession = null) {
        $sdkSession = $this->input->get('sdkSession', true);
        $expectedState = $this->input->get('expectedState', true);
        $expectedNonce = $this->input->get('expectedNonce', true);
        $requestUri = $this->input->server('REQUEST_URI');

        $response = $this->_mobileConnect->HandleUrlRedirect($requestUri, $sdkSession, $expectedState, $expectedNonce, new MobileConnectRequestOptions());
        $tmp = $this->CreateResponse($response);
        return $tmp;
    }

    private function CreateResponse(MobileConnectStatus $status) {
        return $this->output
            ->set_content_type('application/json')
            ->set_status_header(200)
            ->set_output(JsonUtils::toJson(ResponseConverter::Convert($status)));
    }
}
