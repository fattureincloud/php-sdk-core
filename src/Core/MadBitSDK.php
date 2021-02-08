<?php

namespace MadBit\SDK\Core;

use InvalidArgumentException;
use MadBit\SDK\Authentication\AccessToken;
use MadBit\SDK\Authentication\OAuth2Client;
use MadBit\SDK\Exceptions\MadBitSDKException;
use MadBit\SDK\FileUpload\MadBitFile;
use MadBit\SDK\Helpers\MadBitRedirectLoginHelper;
use MadBit\SDK\PersistentData\PersistentDataFactory;
use MadBit\SDK\PersistentData\PersistentDataInterface;
use MadBit\SDK\PseudoRandomString\PseudoRandomStringGeneratorFactory;
use MadBit\SDK\PseudoRandomString\PseudoRandomStringGeneratorInterface;
use MadBit\SDK\Url\MadBitUrlDetectionHandler;
use MadBit\SDK\Url\UrlDetectionInterface;

class MadBitSDK
{
    /**
     * @const string Version number of the MadBit PHP SDK.
     */
    const VERSION = '1.0.0-beta';

    /**
     * @var MadBitApp the MadBitApp entity
     */
    protected $app;

    /**
     * @var MadBitClient the MadBit client service
     */
    protected $client;

    /**
     * @var OAuth2Client The OAuth 2.0 client service.
     */
    protected $oAuth2Client;

    /**
     * @var null|UrlDetectionInterface the URL detection handler
     */
    protected $urlDetectionHandler;

    /**
     * @var null|PseudoRandomStringGeneratorInterface the cryptographically secure pseudo-random string generator
     */
    protected $pseudoRandomStringGenerator;

    /**
     * @var null|AccessToken the default access token to use with requests
     */
    protected $defaultAccessToken;

    /**
     * @var null|PersistentDataInterface the persistent data handler
     */
    protected $persistentDataHandler;

    /**
     * @var null|MadBitResponse stores the last request made to API
     */
    protected $lastResponse;

    /**
     * Instantiates a new MadBitSDK super-class object.
     *
     * @param array $config
     *
     * @throws MadBitSDKException
     */
    public function __construct(array $config = [])
    {
        $config = array_merge([
            'persistent_data_handler' => null,
            'url_detection_handler' => null,
        ], $config);

        if (!$config['app_id']) {
            throw new MadBitSDKException('Required "app_id" key not supplied in config');
        }
        if (!$config['app_secret']) {
            throw new MadBitSDKException('Required "app_secret" key not supplied in config');
        }

        $this->app = new MadBitApp($config['app_id'], $config['app_secret']);
        $this->client = new MadBitClient();
        $this->pseudoRandomStringGenerator = PseudoRandomStringGeneratorFactory::createPseudoRandomStringGenerator(
            $config['pseudo_random_string_generator']
        );
        $this->setUrlDetectionHandler($config['url_detection_handler'] ?: new MadBitUrlDetectionHandler());
        $this->persistentDataHandler = PersistentDataFactory::createPersistentDataHandler(
            $config['persistent_data_handler']
        );

        if (isset($config['default_access_token'])) {
            $this->setDefaultAccessToken($config['default_access_token']);
        }
    }

    /**
     * Returns the MadBitApp entity.
     *
     * @return MadBitApp
     */
    public function getApp(): MadBitApp
    {
        return $this->app;
    }

    /**
     * Returns the MadBitClient service.
     *
     * @return MadBitClient
     */
    public function getClient(): MadBitClient
    {
        return $this->client;
    }

    /**
     * Returns the OAuth 2.0 client service.
     *
     * @return OAuth2Client
     */
    public function getOAuth2Client(): OAuth2Client
    {
        if (!$this->oAuth2Client instanceof OAuth2Client) {
            $app = $this->getApp();
            $client = $this->getClient();
            $this->oAuth2Client = new OAuth2Client($app, $client);
        }

        return $this->oAuth2Client;
    }

    /**
     * Returns the last response returned from API.
     *
     * @return null|MadBitResponse
     */
    public function getLastResponse()
    {
        return $this->lastResponse;
    }

    /**
     * Returns the URL detection handler.
     *
     * @return UrlDetectionInterface
     */
    public function getUrlDetectionHandler(): UrlDetectionInterface
    {
        return $this->urlDetectionHandler;
    }

    /**
     * Returns the default AccessToken entity.
     *
     * @return null|AccessToken
     */
    public function getDefaultAccessToken()
    {
        return $this->defaultAccessToken;
    }

    /**
     * Sets the default access token to use with requests.
     *
     * @param AccessToken|string $accessToken the access token to save
     *
     * @throws InvalidArgumentException
     */
    public function setDefaultAccessToken($accessToken)
    {
        if (is_string($accessToken)) {
            $this->defaultAccessToken = new AccessToken($accessToken);

            return;
        }

        if ($accessToken instanceof AccessToken) {
            $this->defaultAccessToken = $accessToken;

            return;
        }

        throw new InvalidArgumentException('The default access token must be of type "string" or MadBit\SDK\AccessToken');
    }

    /**
     * Returns the redirect login helper.
     *
     * @throws MadBitSDKException
     *
     * @return MadBitRedirectLoginHelper
     */
    public function getRedirectLoginHelper(): MadBitRedirectLoginHelper
    {
        return new MadBitRedirectLoginHelper(
            $this->getOAuth2Client(),
            $this->persistentDataHandler,
            $this->urlDetectionHandler,
            $this->pseudoRandomStringGenerator
        );
    }

    /**
     * Sends a GET request to API and returns the result.
     *
     * @param string                  $endpoint
     * @param null|AccessToken|string $accessToken
     *
     * @throws MadBitSDKException
     *
     * @return MadBitResponse
     */
    public function get(string $endpoint, $accessToken = null): MadBitResponse
    {
        return $this->sendRequest(
            'GET',
            $endpoint,
            $params = [],
            $accessToken
        );
    }

    /**
     * Sends a POST request to API and returns the result.
     *
     * @param string                  $endpoint
     * @param array                   $params
     * @param null|AccessToken|string $accessToken
     *
     * @throws MadBitSDKException
     *
     * @return MadBitResponse
     */
    public function post(string $endpoint, array $params = [], $accessToken = null): MadBitResponse
    {
        return $this->sendRequest(
            'POST',
            $endpoint,
            $params,
            $accessToken
        );
    }

    /**
     * Sends a PUT request to API and returns the result.
     *
     * @param string                  $endpoint
     * @param array                   $params
     * @param null|AccessToken|string $accessToken
     *
     * @throws MadBitSDKException
     *
     * @return MadBitResponse
     */
    public function put(string $endpoint, array $params = [], $accessToken = null): MadBitResponse
    {
        return $this->sendRequest(
            'PUT',
            $endpoint,
            $params,
            $accessToken
        );
    }

    /**
     * Sends a DELETE request to API and returns the result.
     *
     * @param string                  $endpoint
     * @param array                   $params
     * @param null|AccessToken|string $accessToken
     *
     * @throws MadBitSDKException
     *
     * @return MadBitResponse
     */
    public function delete(string $endpoint, array $params = [], $accessToken = null): MadBitResponse
    {
        return $this->sendRequest(
            'DELETE',
            $endpoint,
            $params,
            $accessToken
        );
    }

    /**
     * Sends a request to API and returns the result.
     *
     * @param string                  $method
     * @param string                  $endpoint
     * @param array                   $params
     * @param null|AccessToken|string $accessToken
     *
     * @throws MadBitSDKException
     *
     * @return MadBitResponse
     */
    public function sendRequest(string $method, string $endpoint, array $params = [], $accessToken = null): MadBitResponse
    {
        $accessToken = $accessToken ?: $this->defaultAccessToken;
        $request = $this->request($method, $endpoint, $params, $accessToken);

        return $this->lastResponse = $this->client->sendRequest($request);
    }

    /**
     * Instantiates a new MadBitRequest entity.
     *
     * @param string                  $method
     * @param string                  $endpoint
     * @param array                   $params
     * @param null|AccessToken|string $accessToken
     *
     * @throws MadBitSDKException
     *
     * @return MadBitRequest
     */
    public function request(string $method, string $endpoint, array $params = [], $accessToken = null): MadBitRequest
    {
        $accessToken = $accessToken ?: $this->defaultAccessToken;

        return new MadBitRequest(
            $this->app,
            $accessToken,
            $method,
            $endpoint,
            $params
        );
    }

    /**
     * Factory to create MadBitFile's.
     *
     * @param string $pathToFile
     *
     * @throws MadBitSDKException
     *
     * @return MadBitFile
     */
    public function fileToUpload(string $pathToFile): MadBitFile
    {
        return new MadBitFile($pathToFile);
    }

    /**
     * Changes the URL detection handler.
     *
     * @param UrlDetectionInterface $urlDetectionHandler
     */
    private function setUrlDetectionHandler(UrlDetectionInterface $urlDetectionHandler)
    {
        $this->urlDetectionHandler = $urlDetectionHandler;
    }
}
