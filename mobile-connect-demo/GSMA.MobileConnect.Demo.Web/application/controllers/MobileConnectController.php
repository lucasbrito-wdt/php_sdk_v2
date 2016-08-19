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
use MCSDK\Cache\CacheImpl;
use MCSDK\Utils\RestClient;

class MobileConnectController extends CI_Controller {
    private $_mobileConnect;

    public function __construct() {
        parent::__construct();

        $discoveryService = new DiscoveryService(new RestClient(), new CacheImpl());
        $authentication = new AuthenticationService();
        $identity = new IdentityService(new RestClient());
        $config = new MobileConnectConfig();
        $config->setClientId("");
        $config->setClientSecret("");
        $config->setDiscoveryUrl("https://reference.mobileconnect.io/discovery/");
        $config->setRedirectUrl("http://localhost:8001/mobileconnect.html");

        $this->_mobileConnect = new MobileConnectWebInterface($discoveryService, $authentication, $identity, $config);
    }

    // Route "start_discovery"
    public function StartDiscovery($msisdn = "", $mcc = "", $mnc = "") {
        //TODO: find better way to pass parameters to controller
        $msisdn = $this->input->get('msisdn', true);
        $mcc = $this->input->get('mcc', true);
        $mnc = $this->input->get('mnc', true);

        $response = $this->_mobileConnect->AttemptDiscovery($this->input, $msisdn, $mcc, $mnc, true, new MobileConnectRequestOptions());
        return $this->CreateResponse($response);
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
        $response = $this->_mobileConnect->StartAuthentication($this->input, $sdkSession, $subscriberId, null, null, $options);

        return $this->CreateResponse($response);
    }

    // Route "user_info"
    public function RequestUserInfo($sdkSession = null, $accessToken = null) {
        $sdkSession = $this->input->get('sdkSession', true);
        $accessToken = $this->input->get('accessToken', true);

        $response = $this->_mobileConnect->RequestUserInfo($this->input, $sdkSession, $accessToken, new MobileConnectRequestOptions());
        return $this->CreateResponse($response);
    }

    // Route "identity"
    public function RequestIdentity($sdkSession = null, $accessToken = null) {
        $sdkSession = $this->input->get('sdkSession', true);
        $accessToken = $this->input->get('accessToken', true);

        $response = $this->_mobileConnect->RequestIdentity($this->input, $sdkSession, $accessToken, new MobileConnectRequestOptions());
        return $this->CreateResponse($response);
    }

    // Route ""
    public function HandleRedirect($sdkSession = null, $mcc_mnc = null, $code = null, $expectedState = null, $expectedNonce = null) {
        $sdkSession = $this->input->get('sdkSession', true);
        $expectedState = $this->input->get('expectedState', true);
        $expectedNonce = $this->input->get('expectedNonce', true);
        $requestUri = $this->input->server('REQUEST_URI');

        $response = $this->_mobileConnect->HandleUrlRedirect($this->input, $requestUri, $sdkSession, $expectedState, $expectedNonce);
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
