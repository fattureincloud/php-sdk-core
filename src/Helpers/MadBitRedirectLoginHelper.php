<?php

namespace MadBit\SDK\Helpers;

use MadBit\SDK\Authentication\AccessToken;
use MadBit\SDK\Authentication\OAuth2Client;
use MadBit\SDK\Exceptions\MadBitSDKException;
use MadBit\SDK\PersistentData\MadBitSessionPersistentDataHandler;
use MadBit\SDK\PersistentData\PersistentDataInterface;
use MadBit\SDK\PseudoRandomString\PseudoRandomStringGeneratorFactory;
use MadBit\SDK\PseudoRandomString\PseudoRandomStringGeneratorInterface;
use MadBit\SDK\Url\MadBitUrlDetectionHandler;
use MadBit\SDK\Url\MadBitUrlManipulator;
use MadBit\SDK\Url\UrlDetectionInterface;

class MadBitRedirectLoginHelper
{
    /**
     * @const int The length of CSRF string to validate the login link.
     */
    const CSRF_LENGTH = 32;

    /**
     * @var OAuth2Client The OAuth 2.0 client service.
     */
    protected $oAuth2Client;

    /**
     * @var UrlDetectionInterface the URL detection handler
     */
    protected $urlDetectionHandler;

    /**
     * @var PersistentDataInterface the persistent data handler
     */
    protected $persistentDataHandler;

    /**
     * @var PseudoRandomStringGeneratorInterface the cryptographically secure pseudo-random string generator
     */
    protected $pseudoRandomStringGenerator;

    /**
     * @param OAuth2Client                              $oAuth2Client          The OAuth 2.0 client service.
     * @param null|PersistentDataInterface              $persistentDataHandler the persistent data handler
     * @param null|UrlDetectionInterface                $urlHandler            the URL detection handler
     * @param null|PseudoRandomStringGeneratorInterface $prsg                  the cryptographically secure pseudo-random string generator
     *
     * @throws MadBitSDKException
     */
    public function __construct(OAuth2Client $oAuth2Client, PersistentDataInterface $persistentDataHandler = null, UrlDetectionInterface $urlHandler = null, PseudoRandomStringGeneratorInterface $prsg = null)
    {
        $this->oAuth2Client = $oAuth2Client;
        $this->persistentDataHandler = $persistentDataHandler ?: new MadBitSessionPersistentDataHandler();
        $this->urlDetectionHandler = $urlHandler ?: new MadBitUrlDetectionHandler();
        $this->pseudoRandomStringGenerator = PseudoRandomStringGeneratorFactory::createPseudoRandomStringGenerator($prsg);
    }

    /**
     * Returns the persistent data handler.
     *
     * @return PersistentDataInterface
     */
    public function getPersistentDataHandler()
    {
        return $this->persistentDataHandler;
    }

    /**
     * Returns the URL detection handler.
     *
     * @return UrlDetectionInterface
     */
    public function getUrlDetectionHandler()
    {
        return $this->urlDetectionHandler;
    }

    /**
     * Returns the cryptographically secure pseudo-random string generator.
     *
     * @return PseudoRandomStringGeneratorInterface
     */
    public function getPseudoRandomStringGenerator(): PseudoRandomStringGeneratorInterface
    {
        return $this->pseudoRandomStringGenerator;
    }

    /**
     * Returns the URL to send the user in order to login to the platform with permission(s) to be re-asked.
     *
     * @param string $redirectUrl the URL the platform should redirect users to after login
     * @param array  $scope       list of permissions to request during login
     * @param string $separator   the separator to use in http_build_query()
     *
     * @throws MadBitSDKException
     *
     * @return string
     */
    public function getReRequestUrl(string $redirectUrl, array $scope = [], $separator = '&'): string
    {
        $params = ['auth_type' => 'rerequest'];

        return $this->makeUrl($redirectUrl, $scope, $params, $separator);
    }

    /**
     * Returns the URL to send the user in order to login to the platform with user to be re-authenticated.
     *
     * @param string $redirectUrl the URL the platform should redirect users to after login
     * @param array  $scope       list of permissions to request during login
     * @param string $separator   the separator to use in http_build_query()
     *
     * @throws MadBitSDKException
     *
     * @return string
     */
    public function getReAuthenticationUrl(string $redirectUrl, array $scope = [], $separator = '&'): string
    {
        $params = ['auth_type' => 'reauthenticate'];

        return $this->makeUrl($redirectUrl, $scope, $params, $separator);
    }

    /**
     * Takes a valid code from a login redirect, and returns an AccessToken entity.
     *
     * @param null|string $redirectUrl the redirect URL
     *
     * @throws MadBitSDKException
     *
     * @return null|AccessToken
     */
    public function getAccessToken($redirectUrl = null)
    {
        if (!$code = $this->getCode()) {
            return null;
        }

        $this->validateCsrf();
        $this->resetCsrf();

        $redirectUrl = $redirectUrl ?: $this->urlDetectionHandler->getCurrentUrl();
        // At minimum we need to remove the 'code', 'enforce_https' and 'state' params
        $redirectUrl = MadBitUrlManipulator::removeParamsFromUrl($redirectUrl, ['code', 'enforce_https', 'state']);

        return $this->oAuth2Client->getAccessTokenFromCode($code, $redirectUrl);
    }

    /**
     * Return the error code.
     *
     * @return null|string
     */
    public function getErrorCode(): string
    {
        return $this->getInput('error_code');
    }

    /**
     * Returns the error.
     *
     * @return null|string
     */
    public function getError(): string
    {
        return $this->getInput('error');
    }

    /**
     * Returns the error reason.
     *
     * @return null|string
     */
    public function getErrorReason(): string
    {
        return $this->getInput('error_reason');
    }

    /**
     * Returns the error description.
     *
     * @return null|string
     */
    public function getErrorDescription(): string
    {
        return $this->getInput('error_description');
    }

    /**
     * Validate the request against a cross-site request forgery.
     *
     * @throws MadBitSDKException
     */
    protected function validateCsrf()
    {
        $state = $this->getState();
        if (!$state) {
            throw new MadBitSDKException('Cross-site request forgery validation failed. Required GET param "state" missing.');
        }
        $savedState = $this->persistentDataHandler->get('state');
        if (!$savedState) {
            throw new MadBitSDKException('Cross-site request forgery validation failed. Required param "state" missing from persistent data.');
        }

        if (hash_equals($savedState, $state)) {
            return;
        }

        throw new MadBitSDKException('Cross-site request forgery validation failed. The "state" param from the URL and session do not match.');
    }

    /**
     * Return the code.
     *
     * @return null|string
     */
    protected function getCode(): string
    {
        return $this->getInput('code');
    }

    /**
     * Return the state.
     *
     * @return null|string
     */
    protected function getState(): string
    {
        return $this->getInput('state');
    }

    /**
     * Stores CSRF state and returns a URL to which the user should be sent to in order to continue the login process with the platform.
     *
     * @param string $redirectUrl the URL the platform should redirect users to after login
     * @param array  $scope       list of permissions to request during login
     * @param array  $params      an array of parameters to generate URL
     * @param string $separator   the separator to use in http_build_query()
     *
     * @throws MadBitSDKException
     *
     * @return string
     */
    private function makeUrl(string $redirectUrl, array $scope, array $params = [], $separator = '&'): string
    {
        $state = $this->persistentDataHandler->get('state') ?: $this->pseudoRandomStringGenerator->getPseudoRandomString(static::CSRF_LENGTH);
        $this->persistentDataHandler->set('state', $state);

        return $this->oAuth2Client->getAuthorizationUrl($redirectUrl, $state, $scope, $params, $separator);
    }

    /**
     * Resets the CSRF so that it doesn't get reused.
     */
    private function resetCsrf()
    {
        $this->persistentDataHandler->set('state', null);
    }

    /**
     * Returns a value from a GET param.
     *
     * @param string $key
     *
     * @return null|string
     */
    private function getInput(string $key)
    {
        return isset($_GET[$key]) ? $_GET[$key] : null;
    }
}
