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

use MCSDK\utils\ErrorResponse;
use MCSDK\utils\JsonUtils;
use Zend\Http\Headers;
use MCSDK\discovery\DiscoveryResponse;
use MCSDK\helpers\OperatorUrls;

class JsonUtilsTest extends PHPUnit_Framework_TestCase
{
    private $emptyJsonDoc;
    private $nullLinksJsonDoc;
    private $actualLinksJsonDoc;
    private $emptyLinksJsonDoc;

    public function __construct()
    {
        $this->emptyJsonDoc = new \stdClass();
        $this->nullLinksJsonDoc = new \stdClass();
        $this->nullLinksJsonDoc->links = null;

        $relObject = new \stdClass();
        $relObject->rel = 'rel1';
        $relObject->href = 'http://someurl.com';
        $this->actualLinksJsonDoc = new \stdClass();
        $this->actualLinksJsonDoc->links = array($relObject);

        $this->emptyLinksJsonDoc = new \stdClass();
        $this->emptyLinksJsonDoc->links = array();

        parent::__construct();
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testExtractUrlWithNullJsonDocThrowsException()
    {
        JsonUtils::extractUrl(null, 'href');
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testExtractUrlWithNullRelThrowsException()
    {
        JsonUtils::extractUrl($this->emptyJsonDoc, null);
    }

    public function testExtractUrlWithoutLinksField()
    {
        $response = JsonUtils::extractUrl($this->emptyJsonDoc, 'something');

        $this->assertNull($response);
    }

    public function testExtractUrlWithNullLinksField()
    {
        $response = JsonUtils::extractUrl($this->nullLinksJsonDoc, 'something');

        $this->assertNull($response);
    }

    public function testExtractUrlWithActualLinksField()
    {
        $response = JsonUtils::extractUrl($this->actualLinksJsonDoc, 'rel1');

        $this->assertEquals($response, 'http://someurl.com');
    }

    public function testExtractUrlWithEmptyLinksField()
    {
        $response = JsonUtils::extractUrl($this->emptyLinksJsonDoc, 'rel1');

        $this->assertEquals($response, null);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testGetErrorResponseWithNullJsonDoc()
    {
        JsonUtils::getErrorResponse(null);
    }

    public function testGetErrorResponseWithEmptyJsonDoc()
    {
        $this->assertNull(JsonUtils::getErrorResponse($this->emptyJsonDoc));
    }

    public function testGetErrorResponseWithNoAltErrorDescription()
    {
        $jsonDoc = new \stdClass();
        $jsonDoc->error = 'None descript error';
        $jsonDoc->error_description = 'More information about the error';

        $expectedResponse = new ErrorResponse();
        $expectedResponse->set_error($jsonDoc->error);
        $expectedResponse->set_error_description($jsonDoc->error_description);
        $expectedResponse->set_error_uri(null);

        $this->assertEquals(JsonUtils::getErrorResponse($jsonDoc), $expectedResponse);
    }

    public function testGetErrorResponseWithAltErrorDescriptionAndErrorDescription()
    {
        $jsonDoc = new \stdClass();
        $jsonDoc->error = 'None descript error';
        $jsonDoc->error_description = 'More information about the error';
        $jsonDoc->description = 'This is an alternative description';

        $expectedResponse = new ErrorResponse();
        $expectedResponse->set_error($jsonDoc->error);
        $expectedResponse->set_error_description($jsonDoc->error_description . ' ' . $jsonDoc->description);
        $expectedResponse->set_error_uri(null);

        $this->assertEquals(JsonUtils::getErrorResponse($jsonDoc), $expectedResponse);
    }

    public function testGetErrorResponseWithAltErrorDescriptionAndNoErrorDescription()
    {
        $jsonDoc = new \stdClass();
        $jsonDoc->error = 'None descript error';
        $jsonDoc->description = 'This is an alternative description';

        $expectedResponse = new ErrorResponse();
        $expectedResponse->set_error($jsonDoc->error);
        $expectedResponse->set_error_description($jsonDoc->description);
        $expectedResponse->set_error_uri(null);

        $this->assertEquals(JsonUtils::getErrorResponse($jsonDoc), $expectedResponse);
    }

    public function testParseJsonWithNullJsonString()
    {
        $this->assertNull(JsonUtils::parseJson(null));
    }

    public function testParseJsonWithRealJsonString()
    {
        $stdClass = new \stdClass();
        $stdClass->test = true;
        $jsonString = json_encode($stdClass);

        $this->assertEquals(JsonUtils::parseJson($jsonString), $stdClass);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testParseOperatorUrlsWithNullJsonDoc()
    {
        JsonUtils::parseOperatorUrls(null);
    }

    private function createDiscoveryResponse($json)
    {
        $cached = false;
        $ttl = new DateTime();
        $responseCode = 200;
        $headers = new Headers();
        return new DiscoveryResponse($cached, $ttl, $responseCode, $headers, json_decode($json));
    }

    public function testShouldReturnTheCorrectClientId() {
        $responseJson = '{"response":{"client_id":"a","client_secret":"b","apis":{"operatorid":{"link":[{"rel":"some_rel","href":"some_href"}]}}}}';
        $clientId = JsonUtils::getClientId(json_decode($responseJson));
        $this->assertNotNull($clientId, "a");
    }

    public function testShouldReturnTheCorrectClientSecret() {
        $responseJson = '{"response":{"client_id":"a","client_secret":"b","apis":{"operatorid":{"link":[{"rel":"some_rel","href":"some_href"}]}}}}';
        $clientSecret = JsonUtils::getClientSecret(json_decode($responseJson));
        $this->assertNotNull($clientSecret, "b");
    }

    public function testOperatorUrlsShouldAllBeNull() {
        $responseJson = '{"response":{"client_id":"a","client_secret":"b","apis":{"operatorid":{"link":[{"rel":"some_rel","href":"some_href"}]}}}}';
        $discoveryResponse = $this->createDiscoveryResponse($responseJson);

        $operatorUrls = JsonUtils::parseOperatorUrls($discoveryResponse->getResponseData());
        $discoveryResponse->setOperatorUrls($operatorUrls);

        $this->assertNull($discoveryResponse->getOperatorUrls()->getAuthorization());
        $this->assertNull($discoveryResponse->getOperatorUrls()->getToken());
        $this->assertNull($discoveryResponse->getOperatorUrls()->getUserInfo());
        $this->assertNull($discoveryResponse->getOperatorUrls()->getPremiumInfo());
        $this->assertNull($discoveryResponse->getOperatorUrls()->getOpenidConfiguration());
    }

    public function testOperatorUrlsShouldReturnCorrectValues()
    {
        $responseJson = '{"response":{"client_id":"client","client_secret":"secret","apis":{"operatorid":{"link":[{"rel":"authorization","href":"authUrl"},{"rel":"token","href":"tokenUrl"},{"rel":"userinfo","href":"userinfoUrl"},{"rel":"premiuminfo","href":"premiuminfoUrl"},{"rel":"jwks","href":"jwksUrl"},{"rel":"applicationShortName","href":"test1"},{"rel":"openid-configuration","href":"openidUrl"}]}}}}';
        $discoveryResponse = $this->createDiscoveryResponse($responseJson);

        $operatorUrls = JsonUtils::parseOperatorUrls($discoveryResponse->getResponseData());
        $discoveryResponse->setOperatorUrls($operatorUrls);

        $this->assertTrue($discoveryResponse->getOperatorUrls()->getAuthorization() === 'authUrl');
        $this->assertTrue($discoveryResponse->getOperatorUrls()->getToken() === 'tokenUrl');
        $this->assertTrue($discoveryResponse->getOperatorUrls()->getUserInfo() === 'userinfoUrl');
        $this->assertTrue($discoveryResponse->getOperatorUrls()->getPremiumInfo() === 'premiuminfoUrl');
        $this->assertTrue($discoveryResponse->getOperatorUrls()->getOpenidConfiguration() === 'openidUrl');
    }

    public function testOperatorUrlsShouldOverrideValuesFromProviderMetadata()
    {
        $responseJson = '{"response":{"client_id":"client","client_secret":"secret","apis":{"operatorid":{"link":[{"rel":"authorization","href":"authUrl"},{"rel":"token","href":"tokenUrl"},{"rel":"userinfo","href":"userinfoUrl"},{"rel":"premiuminfo","href":"premiuminfoUrl"},{"rel":"jwks","href":"jwksUrl"},{"rel":"applicationShortName","href":"test1"},{"rel":"openid-configuration","href":"openidUrl"}]}}}}';
        $discoveryResponse = $this->createDiscoveryResponse($responseJson);

        $operatorUrls = JsonUtils::parseOperatorUrls($discoveryResponse->getResponseData());
        $discoveryResponse->setOperatorUrls($operatorUrls);

        $providerMetadata = json_decode('{"authorization_endpoint":"newAuthUrl","token_endpoint":"newTokenUrl","userinfo_endpoint":"newUserinfoUrl","premiuminfo_endpoint":"newPremiuminfoUrl","check_session_iframe":"newCheckSessionIframeUrl","end_session_endpoint":"newEndSessionUrl","jwks_uri":"newJwksUrl"}', true);
        $discoveryResponse->setProviderMetadata($providerMetadata);
        OperatorUrls::overrideUrls($discoveryResponse);

        $this->assertTrue($discoveryResponse->getOperatorUrls()->getAuthorization() === 'newAuthUrl');
        $this->assertTrue($discoveryResponse->getOperatorUrls()->getToken() === 'newTokenUrl');
        $this->assertTrue($discoveryResponse->getOperatorUrls()->getUserInfo() === 'newUserinfoUrl');
        $this->assertTrue($discoveryResponse->getOperatorUrls()->getPremiumInfo() === 'newPremiuminfoUrl');
        $this->assertTrue($discoveryResponse->getOperatorUrls()->getOpenidConfiguration() === 'openidUrl');
    }

    public function testParseRequestTokenResponseWithNullErrorResponse()
    {
        $response = JsonUtils::parseRequestTokenResponse(new \DateTime(), json_encode($this->emptyJsonDoc));

        $this->assertInstanceOf('MCSDK\oidc\RequestTokenResponse', $response);
    }

    public function testParseRequestTokenResponseWithErrorResponse()
    {
        $jsonDoc = $this->emptyJsonDoc;
        $jsonDoc->error = 'None descript error';
        $jsonDoc->description = 'This is an alternative description';

        $response = JsonUtils::parseRequestTokenResponse(new \DateTime(), json_encode($jsonDoc));

        $this->assertInstanceOf('MCSDK\oidc\RequestTokenResponse', $response);
    }

    public function testParseRequestTokenResponseWithTokenStr()
    {
        $jsonDoc = $this->emptyJsonDoc;

        $jwtHeader = new \stdClass();
        $jwtHeader->alg = 'testAlg';
        $jwtHeader->typ = 'testType';

        $jwtPayload = new \stdClass();

        $jsonDoc->id_token = base64_encode(json_encode($jwtHeader)) . '.' . base64_encode(json_encode($jwtPayload)) . '.8234732687';

        $response = JsonUtils::parseRequestTokenResponse(new \DateTime(), json_encode($jsonDoc));

        $this->assertInstanceOf('MCSDK\oidc\RequestTokenResponse', $response);
        $this->assertInstanceOf('MCSDK\oidc\ParsedIdToken', $response->getResponseData()->getParsedIdToken());
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testParseRequestTokenResponseWithInvalidTokenStr()
    {
        $jsonDoc = $this->emptyJsonDoc;

        $jsonDoc->id_token = 'dwh8948h9489f8sbui';

        JsonUtils::parseRequestTokenResponse(new \DateTime(), json_encode($jsonDoc));
    }
}