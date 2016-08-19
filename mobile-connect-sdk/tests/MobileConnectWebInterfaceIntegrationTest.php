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

use MCSDK\Utils\RestResponse;
use MCSDK\Discovery\DiscoveryResponse;
use MCSDK\Discovery\DiscoveryService;
use MCSDK\Discovery\IDiscoveryService;
use MCSDK\Discovery\DiscoveryOptions;
use MCSDK\MobileConnectConfig;
use MCSDK\Cache\ICache;
use MCSDK\Cache\CacheImpl;
use MCSDK\Exceptions\MobileConnectEndpointHttpException;
use MCSDK\Authentication\AuthenticationService;
use MCSDK\Identity\IdentityService;
use MCSDK\MobileConnectWebInterface;
use MCSDK\MobileConnectRequestOptions;
use MCSDK\Utils\HttpUtils;
use MCSDK\Utils\MobileConnectResponseType;

use Zend\Http\Request;
use Zend\Cache\Storage\ClearByNamespaceInterface;
use Zend\Stdlib\ParametersInterface;
use Zend\Stdlib\Traversable;

class FakeRequest extends Request {
    public function ip_address() {
        return "::1";
    }
    public function cookie() {
        return array ();
    }
}

class MobileConnectWebInterfaceIntegrationTest extends PHPUnit_Framework_TestCase {

    const responseJson = "{\"ttl\":1461169322705,\"subscriber_id\":\"e06a09de399ae6c6798c2126e531775ddf3cfe00367af1842534be709fef25e199157c49cc44adf661d286a29afa09c017747fb4383db22b2eaf33db5f878b3ea261c8f342b234e998757e83de23f4a637ce2390453d5d578c76cd65aae99332ee7fbdbd4a140c99babc4e700eae6aa44d3e17ac050771c1fd784fef0214bf770cd0854ea6f4cff87b3ea1e4b25dccd1d340f00eb66c0f041f90596f5236c1017b2541606fff5165320fc4b3381ebfe1fdb848ab04fbedc550bc575ca385b44695a0a9917a368552ee9f8e2178553318a17c32284197631f74f293f30fe6c04f7a77115ec0d2e8ab2a522db88c60263ec1b690ca22540b916e8a9d2c3d820ec1\",\"response\":{\"serving_operator\":\"Example Operator A\",\"country\":\"US\",\"currency\":\"USD\",\"apis\":{\"operatorid\":{\"link\":[{\"href\":\"http://operator_a.sandbox2.mobileconnect.io/oidc/authorize\",\"rel\":\"authorization\"},{\"href\":\"http://operator_a.sandbox2.mobileconnect.io/oidc/accesstoken\",\"rel\":\"token\"},{\"href\":\"http://operator_a.sandbox2.mobileconnect.io/oidc/userinfo\",\"rel\":\"userinfo\"},{\"href\":\"openid profile email\",\"rel\":\"scope\"}]}},\"client_id\":\"66742a85-2282-4747-881d-ed5b7bd74d2d\",\"client_secret\":\"f15199f4-b658-4e58-8bb3-e40998873392\",\"subscriber_id\":\"e06a09de399ae6c6798c2126e531775ddf3cfe00367af1842534be709fef25e199157c49cc44adf661d286a29afa09c017747fb4383db22b2eaf33db5f878b3ea261c8f342b234e998757e83de23f4a637ce2390453d5d578c76cd65aae99332ee7fbdbd4a140c99babc4e700eae6aa44d3e17ac050771c1fd784fef0214bf770cd0854ea6f4cff87b3ea1e4b25dccd1d340f00eb66c0f041f90596f5236c1017b2541606fff5165320fc4b3381ebfe1fdb848ab04fbedc550bc575ca385b44695a0a9917a368552ee9f8e2178553318a17c32284197631f74f293f30fe6c04f7a77115ec0d2e8ab2a522db88c60263ec1b690ca22540b916e8a9d2c3d820ec1\"}}";

    const validOperatorSelectionCallback = "http://localhost:8001/?mcc_mnc=901_01&subscriber_id=33bf6c6172098e9521dee0cb86df822354745a2fd25a74caab18461d7477787a203d144e386f1458707a383acba9f248bf07b245c26f54386039f8943ef19578ad94a4307b633e5e4343cc63510199541d4bb3f2c1dd0a843ce80e825f48f9465476a0c11ff277261cdb1b98495855e3e781611f72aa32ff4dc6078b6d15de233304b17d335f299552a2c3d8e208429d0eb9a3b0ffe131717b393205b45d8ce6f6a43cb30331ebd02291f5ee7ca245630d54fcc29cfe907ba1eb237faadbf8ceb2f9aa936173ab48e8aa05d6f35d71e4164d5a94d8476d616fe3972d43fa97f70d7109456e36fd7f5809a980e98e86ead1643c93f80b2e92f8f599b29bb132a4";
    const noMCCOperatorSelectionCallback = "http://localhost:8001/?subscriber_id=33bf6c6172098e9521dee0cb86df822354745a2fd25a74caab18461d7477787a203d144e386f1458707a383acba9f248bf07b245c26f54386039f8943ef19578ad94a4307b633e5e4343cc63510199541d4bb3f2c1dd0a843ce80e825f48f9465476a0c11ff277261cdb1b98495855e3e781611f72aa32ff4dc6078b6d15de233304b17d335f299552a2c3d8e208429d0eb9a3b0ffe131717b393205b45d8ce6f6a43cb30331ebd02291f5ee7ca245630d54fcc29cfe907ba1eb237faadbf8ceb2f9aa936173ab48e8aa05d6f35d71e4164d5a94d8476d616fe3972d43fa97f70d7109456e36fd7f5809a980e98e86ead1643c93f80b2e92f8f599b29bb132a4";
    const noQueryOperatorSelectionCallback = "http://localhost:8001/";

    const requestUrl = "http://localhost:8080/mobileconnect";

    private static $_testConfig;
    private static $_restClient;
    private static $_cache;
    private static $_discovery;
    private static $_authentication;
    private static $_identity;
    private static $_config;
    private static $_mobileConnect;

    public static function setUpBeforeClass() {
        self::$_restClient = new MockRestClient();
        self::$_cache = new CacheImpl();
        self::$_discovery = new DiscoveryService(self::$_restClient, self::$_cache);
        self::$_authentication = new AuthenticationService(self::$_restClient);
        self::$_identity = new IdentityService(self::$_restClient);

        //self::$_testConfig = TestConfig.GetConfig(TestConfig.DEFAULT_TEST_CONFIG);
        self::$_config = new MobileConnectConfig();
        self::$_config->setDiscoveryUrl("http://discovery.dev.sandbox.mobileconnect.io/v2/discovery");
        self::$_config->setClientId("e058eeb3-8813-417e-b258-4a02729dcf41");
        self::$_config->setClientSecret("235c44a5-51e0-44b1-92e9-e425206206d8");
        self::$_config->setRedirectUrl("http://localhost:8001/mobileconnect.html");

        self::$_mobileConnect = new MobileConnectWebInterface(self::$_discovery, self::$_authentication, self::$_identity, self::$_config);
    }

    public function AttemptDiscoveryShouldSucceedWithTestMSISDN()
    {
        $requestOptions = new MobileConnectRequestOptions();
        $requestOptions->setClientIp("::1");
        $request = new Request();
        //$server = new FakeServer();

        //$request->setServer();
        $request->setMethod(Request::METHOD_GET);
        $request->setUri(self::requestUrl);

        $response = self::$_mobileConnect->AttemptDiscovery($request, "+447700900250", null, null, true, $requestOptions);

        $this->assertEquals(MobileConnectResponseType::StartAuthentication, $response->getResponseType());
        $this->assertNotNull($response->getDiscoveryResponse());
    }
}