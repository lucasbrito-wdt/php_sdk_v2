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

class TokenValidationResult {
    /// <summary>
    /// No validation has occured
    /// </summary>
    const None = 0;
    /// <summary>
    /// Token when signed does not match signature
    /// </summary>
    const InvalidSignature = 1;
    /// <summary>
    /// Token passed all validation steps
    /// </summary>
    const Valid = 2;
    /// <summary>
    /// Key was not retrieved from the jwks url or a jwks url was not present
    /// </summary>
    const JWKSError = 4;
    /// <summary>
    /// The alg claim in the id token header does not match the alg requested or the default alg of RS256
    /// </summary>
    const IncorrectAlgorithm = 8;
    /// <summary>
    /// Neither the azp nor the aud claim in the id token match the client id used to make the auth request
    /// </summary>
    const InvalidAudAndAzp = 16;
    /// <summary>
    /// The iss claim in the id token does not match the expected issuer
    /// </summary>
    const InvalidIssuer = 32;
    /// <summary>
    /// The IdToken has expired
    /// </summary>
    const IdTokenExpired = 64;
    /// <summary>
    /// No key matching the requested key id was found
    /// </summary>
    const NoMatchingKey = 128;
    /// <summary>
    /// Key does not contain the required information to validate against the requested algorithm
    /// </summary>
    const KeyMisformed = 256;
    /// <summary>
    /// Algorithm is unsupported for validation
    /// </summary>
    const UnsupportedAlgorithm = 512;
    /// <summary>
    /// The access token has expired
    /// </summary>
    const AccessTokenExpired = 1024;
    /// <summary>
    /// The access token is null or empty in the token response
    /// </summary>
    const AccessTokenMissing = 2048;
    /// <summary>
    /// The id token is null or empty in the token response
    /// </summary>
    const IdTokenMissing = 4096;
    /// <summary>
    /// The id token is older than the max age specified in the auth stage
    /// </summary>
    const MaxAgePassed = 8192;
    /// <summary>
    /// A longer time than the configured limit has passed since the token was issued
    /// </summary>
    const TokenIssueTimeLimitPassed = 16384;
    /// <summary>
    /// The nonce in the id token claims does not match the nonce specified in the auth stage
    /// </summary>
    const InvalidNonce = 32768;
    /// <summary>
    /// The token response is null or missing required data
    /// </summary>
    const IncompleteTokenResponse = 65536;
}
