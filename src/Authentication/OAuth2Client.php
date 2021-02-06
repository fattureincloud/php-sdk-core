<?php

namespace MadBit\SDK\Authentication;

use MadBit\SDK\Core\MadBitApp;
use MadBit\SDK\Core\MadBitClient;
use MadBit\SDK\Core\MadBitRequest;
use MadBit\SDK\Core\MadBitResponse;
use MadBit\SDK\Core\MadBitSDK;
use MadBit\SDK\Exceptions\MadBitResponseException;
use MadBit\SDK\Exceptions\MadBitSDKException;

/**
 * Class OAuth2Client.
 */
class OAuth2Client
{
    /**
     * @const string The base authorization URL.
     */
    const BASE_AUTHORIZATION_URL = 'https://secure.fattureincloud.it';

    /**
     * The MadBitApp entity.
     *
     * @var MadBitApp
     */
    protected $app;

    /**
     * The MadBit client.
     *
     * @var MadBitClient
     */
    protected $client;

    /**
     * The last request sent to API.
     *
     * @var null|MadBitRequest
     */
    protected $lastRequest;

    /**
     * @param MadBitApp    $app
     * @param MadBitClient $client
     */
    public function __construct(MadBitApp $app, MadBitClient $client)
    {
        $this->app = $app;
        $this->client = $client;
    }

    /**
     * Returns the last MadBitRequest that was sent.
     * Useful for debugging and testing.
     *
     * @return null|MadBitRequest
     */
    public function getLastRequest(): MadBitRequest
    {
        return $this->lastRequest;
    }

    /**
     * Get the metadata associated with the access token.
     *
     * @param AccessToken|string $accessToken the access token to debug
     *
     * @throws MadBitSDKException
     *
     * @return AccessTokenMetadata
     */
    public function debugToken($accessToken): AccessTokenMetadata
    {
        $accessToken = $accessToken instanceof AccessToken ? $accessToken->getValue() : $accessToken;
        $params = ['input_token' => $accessToken];

        $this->lastRequest = new MadBitRequest(
            $this->app,
            $this->app->getAccessToken(),
            'GET',
            '/debug_token',
            $params
        );
        $response = $this->client->sendRequest($this->lastRequest);
        $metadata = $response->getDecodedBody();

        return new AccessTokenMetadata($metadata);
    }

    /**
     * Generates an authorization URL to begin the process of authenticating a user.
     *
     * @param string $redirectUrl the callback URL to redirect to
     * @param string $state       the CSPRNG-generated CSRF value
     * @param array  $scope       an array of permissions to request
     * @param array  $params      an array of parameters to generate URL
     * @param string $separator   the separator to use in http_build_query()
     *
     * @return string
     */
    public function getAuthorizationUrl(string $redirectUrl, string $state, array $scope = [], array $params = [], $separator = '&'): string
    {
        $params += [
            'client_id' => $this->app->getId(),
            'state' => $state,
            'response_type' => 'code',
            'sdk' => 'php-sdk-'.MadBitSDK::VERSION,
            'redirect_uri' => $redirectUrl,
            'scope' => implode(',', $scope),
        ];

        return static::BASE_AUTHORIZATION_URL.'/oauth/authorize?'.http_build_query($params, null, $separator);
    }

    /**
     * Get a valid access token from a code.
     *
     * @param string $code
     * @param string $redirectUri
     *
     * @throws MadBitSDKException
     *
     * @return AccessToken
     */
    public function getAccessTokenFromCode(string $code, $redirectUri = ''): AccessToken
    {
        $params = [
            'code' => $code,
            'redirect_uri' => $redirectUri,
        ];

        return $this->requestAnAccessToken($params);
    }

    /**
     * Exchanges a short-lived access token with a long-lived access token.
     *
     * @param AccessToken|string $accessToken
     *
     * @throws MadBitSDKException
     *
     * @return AccessToken
     */
    public function getLongLivedAccessToken($accessToken): AccessToken
    {
        $accessToken = $accessToken instanceof AccessToken ? $accessToken->getValue() : $accessToken;
        $params = [
            'grant_type' => 'fb_exchange_token',
            'fb_exchange_token' => $accessToken,
        ];

        return $this->requestAnAccessToken($params);
    }

    /**
     * Get a valid code from an access token.
     *
     * @param AccessToken|string $accessToken
     * @param string             $redirectUri
     *
     * @throws MadBitSDKException
     *
     * @return AccessToken
     */
    public function getCodeFromLongLivedAccessToken($accessToken, $redirectUri = ''): AccessToken
    {
        $params = [
            'redirect_uri' => $redirectUri,
        ];

        $response = $this->sendRequestWithClientParams('/oauth/client_code', $params, $accessToken);
        $data = $response->getDecodedBody();

        if (!isset($data['code'])) {
            throw new MadBitSDKException('Code was not returned from API.', 401);
        }

        return $data['code'];
    }

    /**
     * Send a request to the OAuth endpoint.
     *
     * @param array $params
     *
     * @throws MadBitSDKException
     *
     * @return AccessToken
     */
    protected function requestAnAccessToken(array $params): AccessToken
    {
        $response = $this->sendRequestWithClientParams('/oauth/access_token', $params);
        $data = $response->getDecodedBody();

        if (!isset($data['access_token'])) {
            throw new MadBitSDKException('Access token was not returned from API.', 401);
        }

        $expiresAt = 0;
        if (isset($data['expires'])) {
            $expiresAt = time() + $data['expires'];
        } elseif (isset($data['expires_in'])) {
            $expiresAt = time() + $data['expires_in'];
        }

        return new AccessToken($data['access_token'], $expiresAt);
    }

    /**
     * Send a request to API with an app access token.
     *
     * @param string                  $endpoint
     * @param array                   $params
     * @param null|AccessToken|string $accessToken
     *
     * @throws MadBitSDKException
     * @throws MadBitResponseException
     *
     * @return MadBitResponse
     */
    protected function sendRequestWithClientParams(string $endpoint, array $params, $accessToken = null): MadBitResponse
    {
        $params += $this->getClientParams();

        $accessToken = $accessToken ?: $this->app->getAccessToken();

        $this->lastRequest = new MadBitRequest(
            $this->app,
            $accessToken,
            'GET',
            $endpoint,
            $params
        );

        return $this->client->sendRequest($this->lastRequest);
    }

    /**
     * Returns the client_* params for OAuth requests.
     *
     * @return array
     */
    protected function getClientParams(): array
    {
        return [
            'client_id' => $this->app->getId(),
            'client_secret' => $this->app->getSecret(),
        ];
    }
}
