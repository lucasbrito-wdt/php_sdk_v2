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

include __DIR__ . '/../../vendor/autoload.php';

use MCSDK\Utils\JsonWebToken;
use MCSDK\Utils\JWTPart;
use Jose\Factory\JWKFactory;
use Jose\Loader;

class TokenValidation {

    private static function CalculateExpiry($tokenResponse)
    {
        $received = time();
        if (isset($tokenResponse['time_received']))
        {
            $received = strtotime($tokenResponse['time_received']);
        }
        return $received + $tokenResponse['expires_in'];
    }

    public static function ValidateAccessToken($tokenResponse)
    {
        if (empty($tokenResponse['access_token']))
        {
            return TokenValidationResult::AccessTokenMissing;
        }

        if (self::CalculateExpiry($tokenResponse) <= time())
        {
            return TokenValidationResult::AccessTokenExpired;
        }

        return TokenValidationResult::Valid;
    }

    public static function ValidateIdToken($idToken, $clientId, $issuer, $nonce, $maxAge, $keyset) {
        if (empty($idToken)) {
            return TokenValidationResult::IdTokenMissing;
        }

        $result = self::ValidateIdTokenClaims($idToken, $clientId, $issuer, $nonce, $maxAge);
        if ($result != TokenValidationResult::Valid) {
            return $result;
        }
        return self::ValidateIdTokenSignature($idToken, $keyset);
    }

    public static function ValidateIdTokenClaims($idToken, $clientId, $issuer, $nonce, $maxAge) {
        $claims = JsonWebToken::DecodePart($idToken, JWTPart::Payload);
        $claims = json_decode($claims, true);

        if (!empty($nonce) && $claims['nonce'] != $nonce) {
            return TokenValidationResult::InvalidNonce;
        }

        if (!self::DoesAudOrAzpClaimMatchClientId($claims, $clientId)) {
            return TokenValidationResult::InvalidAudAndAzp;
        }

        if ($claims['iss'] != $issuer) {
            return TokenValidationResult::InvalidIssuer;
        }

        $now = time();
        $exp = $claims["exp"] !== null ? $claims["exp"] : null;

        if (empty($exp) || $exp < $now) {
            return TokenValidationResult::IdTokenExpired;
        }

        $authTime = $claims["auth_time"];
        if (!empty($maxAge) && ($authTime + $maxAge < $now)) {
            return TokenValidationResult::MaxAgePassed;
        }

        return TokenValidationResult::Valid;
    }

    private static function DoesAudOrAzpClaimMatchClientId($claims, $clientId)
    {
        $audArray = $claims["aud"];
        if (!empty($audArray) && is_array($audArray) && !in_array($clientId, $audArray))
        {
            return false;
        }

        if (!is_array($audArray) && $claims["aud"] != $clientId)
        {
            return false;
        }

        $azp = $claims["azp"];
        return empty($azp) || ($azp == $clientId);
    }

    public static function ValidateIdTokenSignature($idToken, $keyset) {

        if (empty($keyset)) {
            return TokenValidationResult::JWKSError;
        }

        $header = JsonWebToken::DecodePart($idToken, JWTPart::Header);
        $header = json_decode($header, true);

        $alg = $header["alg"];
        $keyid = isset($header["kid"]) ? $header["kid"] : null;

        $key = null;
        foreach($keyset as $element) {
            if (!isset($element[0]['kid']) && empty($keyid)) {
                if (isset($element[0]["alg"]) && $element[0]["alg"] == $alg) {
                    $key = $element;
                    break;
                }
            }
            if (isset($element[0]['kid']) && $element[0]['kid'] === $keyid) {
                if (isset($element[0]["alg"]) && $element[0]["alg"] === $alg) {
                    $key = $element;
                }
            }
        }

        if (empty($key)) {
            return TokenValidationResult::NoMatchingKey;
        }

        $signature = JsonWebToken::DecodePart($idToken, JWTPart::Signature);
        if (empty($signature)) {
            return TokenValidationResult::InvalidSignature;
        }

        $jwk_set = JWKFactory::createFromValues($keyset);
        $loader = new Loader();
        $isValid = true;

        try {
            $jws = $loader->loadAndVerifySignatureUsingKeySet(
                $idToken,
                $jwk_set,
                ['RS256'],
                $signature_index
            );
        } catch (InvalidArgumentException $ex) {
            return TokenValidationResult::InvalidSignature;
        }
        return TokenValidationResult::Valid;
    }
}
