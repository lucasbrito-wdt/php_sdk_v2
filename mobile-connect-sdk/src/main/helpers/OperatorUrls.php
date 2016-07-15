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

namespace MCSDK\helpers;
use MCSDK\utils\Constants;

class OperatorUrls
{
    private $_authorization;
    private $_token;
    private $_userInfo;
    private $_premiumInfo;
    private $_jwks;
    private $_openIdConfiguration;

    /**
     * The authorization end point if present.
     *
     * @return string The authorization end point or null.
     */
    public function getAuthorization()
    {
        return $this->_authorization;
    }

    /**
     * Set the authorization end point.
     *
     * @param string $authorization The authorization end point.
     */
    public function setAuthorization($authorizationHref)
    {
        $this->_authorization = $authorizationHref;
    }

    /**
     * The token end point if present.
     *
     * @return string The token end point or null.
     */
    public function getToken()
    {
        return $this->_token;
    }

    /**
     * Set the token end point.
     *
     * @param string $token The token end point.
     */
    public function setToken($tokenHref)
    {
        $this->_token = $tokenHref;
    }

    /**
     * The user info end point if present.
     *
     * @return string The user info end point or null.
     */
    public function getUserInfo()
    {
        return $this->_userInfo;
    }

    /**
     * Set the user info end point
     *
     * @param string $userInfo The user info end point.
     */
    public function setUserInfo($userInfoHref)
    {
        $this->_userInfo = $userInfoHref;
    }

    /**
     * The premium info end point if present.
     *
     * @return string The premium info end point or null.
     */
    public function getPremiumInfo()
    {
        return $this->_premiumInfo;
    }

    /**
     * Set the premium info end point
     *
     * @param string $premiumInfo The premium info end point.
     */
    public function setPremiumInfo($premiumInfoHref)
    {
        $this->_premiumInfo = $premiumInfoHref;
    }

    /**
     * Set openid-configuration endpoint
     *
     * @param string $openIdConfiguration The openid-configuration end point
     */
    public function setOpenidConfiguration($openIdConfigurationHref)
    {
        $this->_openIdConfiguration = $openIdConfigurationHref;
    }

    /**
     * The openid-configuration end point if present.
     *
     * @return string The openid-configuration end point or null.
     */
    public function getOpenidConfiguration()
    {
        return $this->_openIdConfiguration;
    }

    public static function overrideUrls($discoveryResponse)
    {
        $providerMetadata = $discoveryResponse->getProviderMetadata();

        $authorizationEndpoint = $providerMetadata[Constants::PROVIDER_METADATA_AUTHORIZATION_ENDPOINT];
        if (!is_null($authorizationEndpoint)) {
            $discoveryResponse->getOperatorUrls()->setAuthorization($authorizationEndpoint);
        }

        $tokenEndpoint = $providerMetadata[Constants::PROVIDER_METADATA_TOKEN_ENDPOINT];
        if (!is_null($tokenEndpoint)) {
            $discoveryResponse->getOperatorUrls()->setToken($tokenEndpoint);
        }

        $userinfoEndpoint = $providerMetadata[Constants::PROVIDER_METADATA_USERINFO_ENDPOINT];
        if (!is_null($userinfoEndpoint)) {
            $discoveryResponse->getOperatorUrls()->setUserInfo($userinfoEndpoint);
        }

        $premiuminfoEndpoint = $providerMetadata[Constants::PROVIDER_METADATA_PREMIUM_INFO_ENDPOINT];
        if (!is_null($premiuminfoEndpoint)) {
            $discoveryResponse->getOperatorUrls()->setPremiumInfo($premiuminfoEndpoint);
        }
    }
}
