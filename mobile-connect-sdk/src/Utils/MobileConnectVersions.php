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

namespace MCSDK\Utils;

use MCSDK\Constants;
use MCSDK\MobileConnectConstants;
use MCSDK\Constants\DefaultOptions;

class MobileConnectVersions
{
    static $_supportedVersionsDict;

    public static function CoerceVersion($version, $scope)
    {
        if(!empty($version))
        {
            return $version;
        }
        $supportedVersion = self::getValue($scope);
        if (!empty($supportedVersion))
        {
            return $supportedVersion;
        }

        return self::$_supportedVersionsDict[MobileConnectConstants::MOBILECONNECT];
    }

    private static function getValue($scope) {
        return isset(self::$_supportedVersionsDict[$scope]) ? self::$_supportedVersionsDict[$scope] : null;
    }
}

MobileConnectVersions::$_supportedVersionsDict = array (
        MobileConnectConstants::MOBILECONNECT => DefaultOptions::VERSION_MOBILECONNECT,
        MobileConnectConstants::MOBILECONNECTAUTHENTICATION => DefaultOptions::VERSION_MOBILECONNECTAUTHN,
        MobileConnectConstants::MOBILECONNECTAUTHORIZATION => DefaultOptions::VERSION_MOBILECONNECTAUTHZ,
        MobileConnectConstants::MOBILECONNECTIDENTITYNATIONALID => DefaultOptions::VERSION_MOBILECONNECTIDENTITY,
        MobileConnectConstants::MOBILECONNECTIDENTITYPHONE => DefaultOptions::VERSION_MOBILECONNECTIDENTITY,
        MobileConnectConstants::MOBILECONNECTIDENTITYSIGNUP => DefaultOptions::VERSION_MOBILECONNECTIDENTITY,
        MobileConnectConstants::MOBILECONNECTIDENTITYSIGNUPPLUS => DefaultOptions::VERSION_MOBILECONNECTIDENTITY,
    );