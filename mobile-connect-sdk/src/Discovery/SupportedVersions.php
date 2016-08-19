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

namespace MCSDK\Discovery;
use MCSDK\MobileConnectConstants;
use MCSDK\Utils\MobileConnectVersions;

class SupportedVersions
{
    private $_initialValues;

    public function __construct($versionSupport = null) {
        $this->_initialValues = empty($versionSupport) ? array () : $versionSupport;
    }

    public function getInitialValues() {
        return $this->_initialValues;
    }
    /**
     * Gets the available mobile connect version for the specified scope value.
     * If versions aren't available then configured default versions will be used.
     * @param string $scope Scope value to retrieve supported version for
     */
    public function GetSupportedVersion($scope) {
        $version = $this->getValue($scope);
        if (empty($version)) {
            $version = $this->getValue(MobileConnectConstants::MOBILECONNECT);
        }

        return MobileConnectVersions::CoerceVersion($version, $scope);
    }

    private function getValue($scope) {
        $result = null;
        foreach($this->_initialValues as $value)
        {
            if (isset($value[$scope])) {
                $result = $value[$scope];
                break;
            }
        }
        return $result;
    }
}
