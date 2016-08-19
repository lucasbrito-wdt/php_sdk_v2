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

namespace MCSDK\utils;

use Zend\Http\Client;
use Zend\Http\Response;
use Zend\Http\Headers;
use Zend\Http\Request;
use MCSDK\Utils\UriBuilder;
use MCSDK\Constants\Header;
use MCSDK\Utils\RestAuthentication;

class RestClient {
    private $_client;
    private $_headers;

    public function __construct() {
        $this->_client = new Client();
        $this->_headers = new Headers();
        $this->_headers->addHeaderLine('Accept: application/json');
    }

    public function get($uri, $auth = null, $sourceIp = null, $params = null, array $cookies = null) {
        $builder = new UriBuilder($uri);
        if (!empty($params)) {
            $builder->addQueryParams($params);
        }

        $this->createRequest($auth, Request::METHOD_GET, $builder->getUri(), $sourceIp, $cookies);
        $response = $this->_client->send();

        return $this->createRestResponse($response);
    }

    public function post($uri, $auth, $formData, $sourceIp, $cookies = null) {
        $this->createRequest($auth, Request::METHOD_POST, $uri, $sourceIp, $cookies);
        $this->_client->setParameterPost($formData);
        $response = $this->_client->send();
        return $this->createRestResponse($response);
    }

    private function createRequest($auth, $method, $uri, $sourceIp, array $cookies = null) {
        $this->_client->setMethod($method);
        $this->_client->setUri($uri);
        if ($sourceIp !== null) {
            $this->_headers->addHeaderLine(Header::X_SOURCE_IP, $sourceIp);
        }
        if (!empty($auth)) {
            $this->_headers->addHeaderLine(sprintf('Authorization: %s %s', $auth->getScheme(), $auth->getParameter()));
        }
        $this->_client->setHeaders($this->_headers);
    }

    private function createRestResponse($response) {
        $headers = $response->getHeaders();
        $restResponse = new RestResponse($response->getStatusCode(), $headers);
        $restResponse->setContent($response->getBody());
        return $restResponse;
    }
}
