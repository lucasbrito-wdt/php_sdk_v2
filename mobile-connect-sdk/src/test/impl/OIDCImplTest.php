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
require_once(dirname(__FILE__) . '/../bootstrap.php');

use MCSDK\impl\OIDCImpl;
use MCSDK\utils\RestResponse;
use MCSDK\discovery\DiscoveryResponse;
use Zend\Http\Headers;
use MCSDK\utils\JsonUtils;

class OidcImplTest extends PHPUnit_Framework_TestCase
{
    private static $oidc;
    private static $stub;
    private static $providerMetadata;
    private static $restResponse;
    private static $discoveryResponse;

    private function createRestResponse($providerMetadata) {
        $uri = "http://someuri";
        $statusCode = 200;
        $headers = new Headers();
        $response = $providerMetadata;
        return new RestResponse($uri, $statusCode, $headers, $response);
    }

    private function createDiscoveryResponse($input) {
        return json_decode($input);
    }

    private function getDiscoveryResponseStub($json)
    {
        $cached = false;
        $ttl = new DateTime();
        $responseCode = 200;
        $headers = new Headers();
        $responseData = json_decode($json);
        $discoveryResponse = new DiscoveryResponse($cached, $ttl, $responseCode, $headers, $responseData);

        return $discoveryResponse;
    }

    public function setUp()
    {
        $providerMetadata = file_get_contents(dirname(__FILE__) . './ProviderMetadata.json', true);
        self::$restResponse = $this->createRestResponse($providerMetadata);
        $response = file_get_contents(dirname(__FILE__) . './DiscoveryResponse.json', true);
        self::$discoveryResponse = $this->createDiscoveryResponse($response);

        self::$stub = $this->getMockBuilder('MCSDK\utils\RestClient')
            ->setMethods(array('callRestEndPoint'))
            ->getMock();
        self::$stub->method('callRestEndPoint')->will($this->returnValue(self::$restResponse));
    }

    public function testShouldRetrieveProviderMetadataAndTheyShouldNotBeNull()
    {
        self::$oidc = new OIDCImpl(self::$stub);
        $json = file_get_contents(dirname(__FILE__) . './DiscoveryResponse.json', true);
        $discoveryResponse = $this->getDiscoveryResponseStub($json);

        $operatorUrls = JsonUtils::parseOperatorUrls($discoveryResponse->getResponseData());
        $discoveryResponse->setOperatorUrls($operatorUrls);

        self::$oidc->requestProviderMetadata($discoveryResponse);

        $this->assertNotNull($discoveryResponse->getProviderMetadata());
    }

    public function testShouldRetrieveProviderMetadataAndTheyShouldBeNull()
    {
        self::$oidc = new OIDCImpl(self::$stub);
        $json = '{"response":{"client_id":"a","client_secret":"b","apis":{"operatorid":{"link":[{"rel":"some_rel","href":"some_href"}]}}}}';
        $discoveryResponse = $this->getDiscoveryResponseStub($json);

        $operatorUrls = JsonUtils::parseOperatorUrls($discoveryResponse->getResponseData());
        $discoveryResponse->setOperatorUrls($operatorUrls);
        self::$oidc->requestProviderMetadata($discoveryResponse);

        $this->assertNull($discoveryResponse->getProviderMetadata());
    }
}
