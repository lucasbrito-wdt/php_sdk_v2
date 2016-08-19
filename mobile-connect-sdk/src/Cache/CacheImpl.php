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

namespace MCSDK\cache;

use MCSDK\utils\Constants;
use Zend\Cache\Storage\Adapter\Filesystem;
use Zend\Cache\Storage\Plugin\ExceptionHandler;
use MCSDK\Discovery\DiscoveryResponse;
use Zend\Http\Headers;

/**
 * Implementation of ICache that uses a Zend Cache instance as a
 * backing store.
 */
class CacheImpl implements ICache
{
    private $_cache;

    /**
     * CacheImpl constructor.
     */
    public function __construct()
    {
        $this->_cache = new Filesystem();
        $this->_cache->getOptions()->setNamespace('/');
        $this->_cache->getOptions()->setTtl(36000);

        $plugin = new ExceptionHandler();
        $plugin->getOptions()->setThrowExceptions(true);
        $this->_cache->addPlugin($plugin);
    }

    public function add($mcc, $mnc, $value) {
        $this->_cache->setItem($this->concatKey($mcc, $mnc), serialize($value));
    }

    public function addKey($key, $value) {
        $value->setTimeCachedUtc(gmdate("Y-m-d\TH:i:s\Z"));
        $this->_cache->setItem($key, serialize($value));
    }

    /**
     * Return the cache object
     *
     * @return Filesystem
     */
    public function getCache()
    {
        return $this->_cache;
    }

    /**
     * Get cache value by key
     *
     * @param CacheKey $key The cache key
     * @return stdClass|null
     */

    public function getKey($key, $removeIfExpired = true) {
        if (empty($key)) {
            return;
        }
        $response = unserialize($this->_cache->getItem($key));
        if ($response == false) {
            return null;
        }
        $response->setCached(true);
        $response->MarkExpired($this->checkIsExpired($response));
        if ($removeIfExpired && !empty($response) && $response->hasExpired()) {
            $this->removeKey($key);
        }
        return $response;
    }

    public function checkIsExpired($value) {
        $isExpired = false;
        return $isExpired || $value->HasExpired();
    }

    public function get($mcc, $mnc) {
        if (empty($mcc) || empty($mnc)) {
            return;
        }
        $key = $this->concatKey($mcc, $mnc);

        //$value = unserialize($this->_cache->getItem($key));
        $value = $this->getKey($key);
        if ($value == false) {
            return null;
        }
        //$this->remove($mcc, $mnc);
        return $value;
    }

    private function concatKey($mcc, $mnc) {
        return $mcc . '_' . $mnc;
    }

    /**
     * Remove cache value by key
     *
     * @param CacheKey $key
     */
    public function remove($mcc, $mnc)
    {
        $key = $this->concatKey($mcc, $mnc);
        $this->validateKey($key);
        $this->_cache->removeItem($key);
    }

    public function removeKey($key)
    {
        $this->validateKey($key);
        $this->_cache->removeItem($key);
    }

    /**
     * Clear the whole cache
     */
    public function clear()
    {
        //$this->_cache->clearByNamespace('/');
        $this->_cache->clearByPrefix('9');
    }

    /**
     * Determine if a given parameter is a valid DiscoveryCacheKey
     *
     * @param mixed $key param to be tested
     */
    private function validateKey($key)
    {
        if (is_null($key)) {
            throw new \InvalidArgumentException("Key cannot be null");
        }
    }

    /**
     * Determine is a value is a valid stdClass
     *
     * @param mixed $value param to be tested
     */
    private function validateValue($value)
    {
        if (is_null($value)) {
            throw new \InvalidArgumentException("Value cannot be null");
        }
    }

    /**
     * Instantiate a new stdClass object
     *
     * @param stdClass $value the value to be cloned
     * @return stdClass a new instance of the stdClass using a given string value and ttl
     */
    private function buildCacheValue(CacheValue $value)
    {
        $copy = clone $value;
        unset($copy->{Constants::SUBSCRIBER_ID_FIELD_NAME});

        return new CacheValue($copy->getValue(), $copy->getTtl());
    }
}
