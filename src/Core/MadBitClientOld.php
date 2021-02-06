<?php

namespace MadBit\SDK\Core;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Token\AccessTokenInterface;
use MadBit\SDK\OAuth\Provider\OAuthProvider;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class MadBitClientOld
{
    /**
     * The OAuth authorization server domain.
     *
     * @var string $domain
     */
    protected $domain;

    /**
     * The API server domain.
     *
     * @var string $apiDomain
     */
    protected $apiDomain;
    /**
     * The OAuth provider.
     *
     * @var OAuthProvider $oauthProvider
     */
    protected $oauthProvider;

    /**
     * The OAuth access token.
     *
     * @var AccessToken|null $accessToken
     */
    protected $accessToken;

    /**
     * The last occurred exception.
     *
     * @var Exception|null $lastError
     */
    protected $lastError;

    /**
     * The Guzzle HTTP client.
     *
     * @var Client $httpClient
     */
    protected $httpClient;

    /**
     * MadBitClient constructor.
     *
     * @param string $clientId
     * @param string $clientSecret
     * @param string $redirectUri
     */
    public function __construct($clientId, $clientSecret, $redirectUri)
    {
        $this->oauthProvider = new OAuthProvider([
            'clientId' => $clientId,
            'clientSecret' => $clientSecret,
            'redirectUri' => $redirectUri,
            'domain' => $this->domain,
            'apiDomain' => $this->apiDomain,
        ]);

        $this->httpClient = new Client();
    }

    /**
     * Return the authorization url to redirect to and the current OAuth state to store somewhere.
     *
     * @return array
     */
    public function getAuthorizationParams()
    {
        // Fetch the authorization URL from the provider; this returns the
        // urlAuthorize option and generates and applies any necessary parameters
        // (e.g. state).
        $authorizationUrl = $this->oauthProvider->getAuthorizationUrl();

        return [
            'authorization_url' => $authorizationUrl,
            'state' => $this->oauthProvider->getState(),
        ];
    }

    /**
     * @param $authorizationCode
     * @return AccessToken|AccessTokenInterface|null
     * @throws IdentityProviderException
     */
    public function getAccessToken($authorizationCode)
    {
        try {
            // Try to get an access token using the authorization code grant.
            $this->accessToken = $this->oauthProvider->getAccessToken('authorization_code', [
                'code' => $authorizationCode,
            ]);
            return $this->accessToken;
        } catch (IdentityProviderException $e) {
            // Failed to get the access token.
            $this->lastError = $e;
            throw $e;
        }
    }

    /**
     * @return ResourceOwnerInterface
     * @throws Exception
     */
    public function getResourceOwner()
    {
        try {
            return $this->oauthProvider->getResourceOwner($this->accessToken);
        } catch (Exception $e) {
            // Failed to get the access token or user details.
            $this->lastError = $e;
            throw $e;
        }
    }

    /**
     * @param $method
     * @param $action
     * @return RequestInterface
     */
    protected function getAuthenticatedRequest($method, $action)
    {
        return $this->oauthProvider->getAuthenticatedRequest(
            $method,
            $this->oauthProvider->getApiDomain() . '/' . $action,
            $this->accessToken
        );
    }

    /**
     * @param $method
     * @param $action
     * @return ResponseInterface
     * @throws GuzzleException
     */
    public function executeAuthenticatedRequest($method, $action)
    {
        $request = $this->getAuthenticatedRequest($method, $action);
        return $this->httpClient->send($request);
    }
}
