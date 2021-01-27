<?php

namespace MadBit\SDK\Core;

use Exception;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use League\OAuth2\Client\Token\AccessTokenInterface;
use MadBit\SDK\OAuth\Provider\MadBitProvider;
use Psr\Http\Message\RequestInterface;

class MadBitClient
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
     * @var MadBitProvider $provider
     */
    protected $provider;

    /**
     * The OAuth access token.
     *
     * @var AccessTokenInterface|null $accessToken
     */
    protected $accessToken;

    /**
     * The last occurred exception.
     *
     * @var Exception|null $lastError
     */
    protected $lastError;

    /**
     * MadBitClient constructor.
     *
     * @param string $clientId
     * @param string $clientSecret
     * @param string $redirectUri
     */
    public function __construct($clientId, $clientSecret, $redirectUri)
    {
        $this->provider = new MadBitProvider([
            'clientId' => $clientId,
            'clientSecret' => $clientSecret,
            'redirectUri' => $redirectUri,
            'domain' => $this->domain,
            'apiDomain' => $this->apiDomain,
        ]);
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
        $authorizationUrl = $this->provider->getAuthorizationUrl();

        return [
            'authorization_url' => $authorizationUrl,
            'state' => $this->provider->getState(),
        ];
    }

    /**
     * @param $authorizationCode
     * @throws IdentityProviderException
     */
    public function getAccessToken($authorizationCode)
    {
        try {
            // Try to get an access token using the authorization code grant.
            $this->accessToken = $this->provider->getAccessToken('authorization_code', [
                'code' => $authorizationCode
            ]);
        } catch (IdentityProviderException $e) {
            // Failed to get the access token or user details.
            $this->lastError = $e;
            throw $e;
        }
    }

    /**
     * @param $accessToken
     * @return ResourceOwnerInterface
     * @throws Exception
     */
    public function getResourceOwner($accessToken)
    {
        try {
            return $this->provider->getResourceOwner($accessToken);
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
    public function executeAuthenticatedRequest($method, $action)
    {
        return $this->provider->getAuthenticatedRequest(
            $method,
            $this->provider->getApiDomain() . '/' . $action,
            $this->accessToken
        );
    }
}
