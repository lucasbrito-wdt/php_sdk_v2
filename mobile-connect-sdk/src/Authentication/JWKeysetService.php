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

namespace MCSDK\Authentication;
use MCSDK\Cache\CacheImpl;
use MCSDK\Utils\RestClient;

class JWKeysetService implements IJWKeysetService {
    private $_client;
    private $_cache;

    public function __construct(RestClient $client, CacheImpl $cache) {
        $this->_client = $client;
        $this->_cache = $cache;
    }

    public function RetrieveJWKS($url) {
        $cached = $this->RetrieveFromCache($url);
        if (!empty($cached) && !$cached->HasExpired()) {
            return $cached;
        }
        $response = null;
        try {
            $response = $this->_client->Get($url);
        } catch (Zend\Http\Exception\RuntimeException $ex) {
            return $cached;
        } catch (Zend\Http\Client\Exception\RuntimeException $ex) {
            return $cached;
        } catch (Exception $ex) {
            return $cached;
        }
        //$response = $response->getContent();
        $jwks = new JWKeyset($response->getContent());

        //$jwks = json_decode($response, true);
        //var_dump($jwks);
        //$this->AddToCache($url, $jwks);

        return $jwks;
    }

    private function RetrieveFromCache($url) {
        if (empty($this->_cache)) {
            return null;
        }
        $key = hash("md5", $url);
        return $this->_cache->getKey($key);
    }

    private function AddToCache($url, $keyset) {
        if (empty($this->_cache) || empty($keyset)) {
            return;
        }
        $this->_cache->AddKey($url, $keyset);
    }

}
