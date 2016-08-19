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

/// <summary>
/// Class to hold details parsed from the discovery redirect
/// </summary>
class ParsedDiscoveryRedirect
{
    private $_selectedMCC;
    private $_selectedMNC;
    private $_encryptedMSISDN;

    /// <summary>
    /// The Mobile Country Code of the selected operator
    /// </summary>
    public function getSelectedMCC()
    {
        return $this->_selectedMCC;
    }

    /// <summary>
    /// The Mobile Network Code of the selected operator
    /// </summary>
    public function getSelectedMNC()
    {
        return $this->_selectedMNC;
    }

    /// <summary>
    /// The encrypted MSISDN is specified
    /// </summary>
    public function getEncryptedMSISDN()
    {
        return $this->_encryptedMSISDN;
    }

    /// <summary>
    /// Returns true if data exists for MCC and MNC
    /// </summary>
    public function HasMCCAndMNC()
    {
        return isset($this->_selectedMCC) && isset($this->_selectedMNC);
    }

    /// <summary>
    /// Creates a ParsedDiscoveryRedirect instance with the specified values
    /// </summary>
    /// <param name="selectedMCC">The selected mobile country code</param>
    /// <param name="selectedMNC">The selected mobile network code</param>
    /// <param name="encryptedMSISDN">The encrypted MSISDN or subscriber id</param>
    public function __construct($selectedMCC, $selectedMNC, $encryptedMSISDN)
    {
        $this->_selectedMCC = $selectedMCC;
        $this->_selectedMNC = $selectedMNC;
        $this->_encryptedMSISDN = $encryptedMSISDN;
    }
}
