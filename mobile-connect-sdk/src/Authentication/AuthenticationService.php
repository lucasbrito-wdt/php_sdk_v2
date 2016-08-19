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

use MCSDK\Utils\RestClient;
use MCSDK\Constants\Scope;
use MCSDK\Constants\Parameters;
use MCSDK\Constants\DefaultOptions;
use MCSDK\Authentication\IAuthenticationService;
use MCSDK\Utils\ValidationUtils;
use MCSDK\Utils\UriBuilder;
use MCSDK\MobileConnectConstants;
use MCSDK\Utils\RestAuthentication;
use MCSDK\Utils\MobileConnectVersions;
use MCSDK\Utils\Scopes;
use MCSDK\Discovery\SupportedVersions;

class AuthenticationService implements IAuthenticationService {
    private $_client;

    public function __construct(RestClient $client = null) {
        if (empty($client)) {
            $this->_client = new RestClient();
        } else {
            $this->_client = $client;
        }
    }

    public function StartAuthentication($clientId, $authorizeUrl, $redirectUrl, $state, $nonce,
        $encryptedMSISDN, $versions = null, AuthenticationOptions $options = null) {
        ValidationUtils::validateParameter($clientId, "clientId");
        ValidationUtils::validateParameter($authorizeUrl, "authorizeUrl");
        ValidationUtils::validateParameter($redirectUrl, "redirectUrl");
        ValidationUtils::validateParameter($state, "state");
        ValidationUtils::validateParameter($nonce, "nonce");

        if (empty($options)) {
            $options = new AuthenticationOptions();
        }

        if (empty($options->getScope())) {
            $options->setScope("");
        }

        $shouldUseAuthorize = $this->shouldUseAuthorize($options);

        if ($shouldUseAuthorize) {
            ValidationUtils::validateParameter($options->getContext(), "options->getContext()");
            ValidationUtils::validateParameter($options->getClientName(), "options->getClientName()");
        }

        $options->setState($state);
        $options->setNonce($nonce);
        if ($options->getLoginHint() === null) {
            if (!empty($encryptedMSISDN)) {
                $options->setLoginHint("ENCR_MSISDN:" . $encryptedMSISDN);
            }
        }
        $options->setRedirectUrl($redirectUrl);
        $options->setClientId($clientId);

        $version = $this->CoerceAuthenticationScope($options->getScope(), $shouldUseAuthorize, $versions);
        $options->setScope($version);

        $build = new UriBuilder($authorizeUrl);
        $build->AddQueryParams($this->getAuthenticationQueryParams($options, $shouldUseAuthorize, $version));

        $response = new StartAuthenticationResponse();
        $response->setUrl($build->getUri());
        return $response;
    }

    public function RequestToken($clientId, $clientSecret, $requestTokenUrl, $redirectUrl, $code) {
        ValidationUtils::validateParameter($clientId, "clientId");
        ValidationUtils::validateParameter($clientSecret, "clientSecret");
        ValidationUtils::validateParameter($requestTokenUrl, "requestTokenUrl");
        ValidationUtils::validateParameter($redirectUrl, "redirectUrl");
        ValidationUtils::validateParameter($code, "code");

        try {
            $formData = array (
                Parameters::AUTHENTICATION_REDIRECT_URI => $redirectUrl,
                Parameters::CODE => $code,
                Parameters::GRANT_TYPE => DefaultOptions::GRANT_TYPE
            );
            $authentication = RestAuthentication::Basic($clientId, $clientSecret);
            $response = $this->_client->post($requestTokenUrl, $authentication, $formData, null, null);

            $tokenResponse = new RequestTokenResponse($response);

            return $tokenResponse;
        } catch (Zend\Http\Exception\RuntimeException $ex) {
            throw new MobileConnectEndpointHttpException($ex->getMessage(), $ex);
        } catch (Zend\Http\Client\Exception\RuntimeException $ex) {
            throw new MobileConnectEndpointHttpException($ex->getMessage(), $ex);
        } catch (Exception $ex) {
            throw new MobileConnectEndpointHttpException($ex->getMessage(), $ex);
        }
    }

    private function shouldUseAuthorize(AuthenticationOptions $options) {
        $authnIndex = stripos($options->getScope(), Scope::AUTHN);
        $authnRequested = ($authnIndex !== false);
        $mcProductRequested = strripos($options->getScope(), Scope::MCPREFIX) != $authnIndex;

        if($mcProductRequested)
        {
            return true;
        }
        if(!$authnRequested && !empty($options->getContext()))
        {
            return true;
        }
        return false;
    }

    public function getAuthenticationQueryParams(AuthenticationOptions $options, $useAuthorize, $version) {
        $authParamters = array (
            Parameters::AUTHENTICATION_REDIRECT_URI => $options->getRedirectUrl(),
            Parameters::CLIENT_ID => $options->getClientId(),
            Parameters::RESPONSE_TYPE => DefaultOptions::AUTHENTICATION_RESPONSE_TYPE,
            Parameters::SCOPE => $options->getScope(),
            Parameters::ACR_VALUES => $options->getAcrValues(),
            Parameters::STATE => $options->getState(),
            Parameters::NONCE => $options->getNonce(),
            Parameters::DISPLAY => $options->getDisplay(),
            Parameters::PROMPT => $options->getPrompt(),
            Parameters::MAX_AGE => $options->getMaxAge(),
            Parameters::UI_LOCALES => $options->getUiLocales(),
            Parameters::CLAIMS_LOCALES => $options->getClaimsLocales(),
            Parameters::ID_TOKEN_HINT => $options->getIdTokenHint(),
            Parameters::LOGIN_HINT => $options->getLoginHint(),
            Parameters::DTBS => $options->getDtbs(),
            Parameters::CLAIMS => $this->getClaimsString($options),
            Parameters::VERSION => $version
        );

        if ($useAuthorize) {
            $authParamters[Parameters::CLIENT_NAME] = $options->getClientName();
            $authParamters[Parameters::CONTEXT] = $options->getContext();
            $authParamters[Parameters::BINDING_MESSAGE] = $options->getBindingMessage();
        }

        return $authParamters;
    }

    private function getClaimsString($options) {
        return null;
    }

    /**
     * Returns a modified scope value based on the version required. Depending on the version the value mc_authn may be added or removed
     * @param string $scopeRequested Request scope value
     * @param SupportedVersions $versions SupportedVersions from ProviderMetadata, used for finding the supported version for the requested auth type
     * @param bool $shouldUseAuthorize If mc_authz should be used over mc_authn
     * @param string $version Supported version of the scope selected to use
     * @return Returns a modified scope value with mc_authn removed or added
     */
    private function CoerceAuthenticationScope($scopeRequested, $shouldUseAuthorize, SupportedVersions $versions = null) {
        $requiredScope = $shouldUseAuthorize === true ? MobileConnectConstants::MOBILECONNECTAUTHORIZATION : MobileConnectConstants::MOBILECONNECTAUTHENTICATION;
        $disallowedScope = $shouldUseAuthorize === true ? Scope::AUTHN : Scope::AUTHZ;

        $versions = empty($versions) ? new SupportedVersions() : $versions;
        $version = $versions->GetSupportedVersion($requiredScope);

        $splitScope = explode(" ", $scopeRequested);

        $splitScope = Scopes::CoerceOpenIdScope($splitScope, $requiredScope);

        $key = array_search($disallowedScope, $splitScope);
        if($key !== false){
            unset($splitScope[$key]);
        }

        if (!$shouldUseAuthorize && ($version == DefaultOptions::VERSION_MOBILECONNECTAUTHN)) {
            $key = array_search(Scope::AUTHN, $splitScope);
            if($key !== false){
                unset($splitScope[$key]);
            }
        }
        return Scopes::CreateScope($splitScope);
    }
}
