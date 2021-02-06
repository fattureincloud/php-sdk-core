<?php

namespace MadBit\SDK\Core;

use MadBit\SDK\Authentication\AccessToken;
use MadBit\SDK\Exceptions\MadBitSDKException;
use MadBit\SDK\FileUpload\MadBitFile;
use MadBit\SDK\Http\RequestBodyMultipart;
use MadBit\SDK\Http\RequestBodyUrlEncoded;
use MadBit\SDK\Url\MadBitUrlManipulator;

class MadBitRequest
{
    /**
     * @var MadBitApp the MadBit app entity
     */
    protected $app;

    /**
     * @var null|string the access token to use for this request
     */
    protected $accessToken;

    /**
     * @var string the HTTP method for this request
     */
    protected $method;

    /**
     * @var string the API endpoint for this request
     */
    protected $endpoint;

    /**
     * @var array the headers to send with this request
     */
    protected $headers = [];

    /**
     * @var array the parameters to send with this request
     */
    protected $params = [];

    /**
     * @var array the files to send with this request
     */
    protected $files = [];

    /**
     * Creates a new Request entity.
     *
     * @param null|MadBitApp          $app
     * @param null|AccessToken|string $accessToken
     * @param null|string             $method
     * @param null|string             $endpoint
     * @param null|array              $params
     *
     * @throws MadBitSDKException
     */
    public function __construct(MadBitApp $app = null, $accessToken = null, $method = null, $endpoint = null, array $params = [])
    {
        $this->setApp($app);
        $this->setAccessToken($accessToken);
        $this->setMethod($method);
        $this->setEndpoint($endpoint);
        $this->setParams($params);
    }

    /**
     * Set the access token for this request.
     *
     * @param mixed $accessToken
     *
     * @return MadBitRequest
     */
    public function setAccessToken($accessToken): MadBitRequest
    {
        $this->accessToken = $accessToken;
        if ($accessToken instanceof AccessToken) {
            $this->accessToken = $accessToken->getValue();
        }

        return $this;
    }

    /**
     * Sets the access token with one harvested from a URL or POST params.
     *
     * @param string $accessToken the access token
     *
     * @throws MadBitSDKException
     *
     * @return MadBitRequest
     */
    public function setAccessTokenFromParams(string $accessToken): MadBitRequest
    {
        $existingAccessToken = $this->getAccessToken();
        if (!$existingAccessToken) {
            $this->setAccessToken($accessToken);
        } elseif ($accessToken !== $existingAccessToken) {
            throw new MadBitSDKException('Access token mismatch. The access token provided in the MadBitRequest and the one provided in the URL or POST params do not match.');
        }

        return $this;
    }

    /**
     * Return the access token for this request.
     *
     * @return null|string
     */
    public function getAccessToken(): string
    {
        return $this->accessToken;
    }

    /**
     * Return the access token for this request as an AccessToken entity.
     *
     * @return null|AccessToken
     */
    public function getAccessTokenEntity()
    {
        return $this->accessToken ? new AccessToken($this->accessToken) : null;
    }

    /**
     * Set the MadBitApp entity used for this request.
     *
     * @param null|MadBitApp $app
     */
    public function setApp(MadBitApp $app = null)
    {
        $this->app = $app;
    }

    /**
     * Return the MadBitApp entity used for this request.
     *
     * @return null|MadBitApp
     */
    public function getApp(): MadBitApp
    {
        return $this->app;
    }

    /**
     * Generate an app secret proof to sign this request.
     *
     * @return null|string
     */
    public function getAppSecretProof()
    {
        if (!$accessTokenEntity = $this->getAccessTokenEntity()) {
            return null;
        }

        return $accessTokenEntity->getAppSecretProof($this->app->getSecret());
    }

    /**
     * Validate that an access token exists for this request.
     *
     * @throws MadBitSDKException
     */
    public function validateAccessToken()
    {
        $accessToken = $this->getAccessToken();
        if (!$accessToken) {
            throw new MadBitSDKException('You must provide an access token.');
        }
    }

    /**
     * Set the HTTP method for this request.
     *
     * @param mixed $method
     */
    public function setMethod($method)
    {
        $this->method = strtoupper($method);
    }

    /**
     * Return the HTTP method for this request.
     *
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * Validate that the HTTP method is set.
     *
     * @throws MadBitSDKException
     */
    public function validateMethod()
    {
        if (!$this->method) {
            throw new MadBitSDKException('HTTP method not specified.');
        }

        if (!in_array($this->method, ['GET', 'POST', 'PUT', 'DELETE'])) {
            throw new MadBitSDKException('Invalid HTTP method specified.');
        }
    }

    /**
     * Set the endpoint for this request.
     *
     * @param mixed $endpoint
     *
     * @throws MadBitSDKException
     *
     * @return MadBitRequest
     */
    public function setEndpoint($endpoint): MadBitRequest
    {
        // Harvest the access token from the endpoint to keep things in sync
        $params = MadBitUrlManipulator::getParamsAsArray($endpoint);
        if (isset($params['access_token'])) {
            $this->setAccessTokenFromParams($params['access_token']);
        }

        // Clean the token & app secret proof from the endpoint.
        $filterParams = ['access_token', 'appsecret_proof'];
        $this->endpoint = MadBitUrlManipulator::removeParamsFromUrl($endpoint, $filterParams);

        return $this;
    }

    /**
     * Return the endpoint for this request.
     *
     * @return string
     */
    public function getEndpoint(): string
    {
        // For batch requests, this will be empty
        return $this->endpoint;
    }

    /**
     * Generate and return the headers for this request.
     *
     * @return array
     */
    public function getHeaders(): array
    {
        $headers = static::getDefaultHeaders();

        return array_merge($this->headers, $headers);
    }

    /**
     * Set the headers for this request.
     *
     * @param array $headers
     */
    public function setHeaders(array $headers)
    {
        $this->headers = array_merge($this->headers, $headers);
    }

    /**
     * Set the params for this request.
     *
     * @param array $params
     *
     * @throws MadBitSDKException
     *
     * @return MadBitRequest
     */
    public function setParams(array $params = []): MadBitRequest
    {
        if (isset($params['access_token'])) {
            $this->setAccessTokenFromParams($params['access_token']);
        }

        // Don't let these buggers slip in.
        unset($params['access_token'], $params['appsecret_proof']);

        $params = $this->sanitizeFileParams($params);
        $this->dangerouslySetParams($params);

        return $this;
    }

    /**
     * Set the params for this request without filtering them first.
     *
     * @param array $params
     *
     * @return MadBitRequest
     */
    public function dangerouslySetParams(array $params = []): MadBitRequest
    {
        $this->params = array_merge($this->params, $params);

        return $this;
    }

    /**
     * Iterate over the params and pull out the file uploads.
     *
     * @param array $params
     *
     * @return array
     */
    public function sanitizeFileParams(array $params): array
    {
        foreach ($params as $key => $value) {
            if ($value instanceof MadBitFile) {
                $this->addFile($key, $value);
                unset($params[$key]);
            }
        }

        return $params;
    }

    /**
     * Add a file to be uploaded.
     *
     * @param string     $key
     * @param MadBitFile $file
     */
    public function addFile(string $key, MadBitFile $file)
    {
        $this->files[$key] = $file;
    }

    /**
     * Removes all the files from the upload queue.
     */
    public function resetFiles()
    {
        $this->files = [];
    }

    /**
     * Get the list of files to be uploaded.
     *
     * @return array
     */
    public function getFiles(): array
    {
        return $this->files;
    }

    /**
     * Let's us know if there is a file upload with this request.
     *
     * @return bool
     */
    public function containsFileUploads(): bool
    {
        return !empty($this->files);
    }

    /**
     * Returns the body of the request as multipart/form-data.
     *
     * @return RequestBodyMultipart
     */
    public function getMultipartBody(): RequestBodyMultipart
    {
        $params = $this->getPostParams();

        return new RequestBodyMultipart($params, $this->files);
    }

    /**
     * Returns the body of the request as URL-encoded.
     *
     * @return RequestBodyUrlEncoded
     */
    public function getUrlEncodedBody(): RequestBodyUrlEncoded
    {
        $params = $this->getPostParams();

        return new RequestBodyUrlEncoded($params);
    }

    /**
     * Generate and return the params for this request.
     *
     * @return array
     */
    public function getParams(): array
    {
        $params = $this->params;

        $accessToken = $this->getAccessToken();
        if ($accessToken) {
            $params['access_token'] = $accessToken;
            $params['appsecret_proof'] = $this->getAppSecretProof();
        }

        return $params;
    }

    /**
     * Only return params on POST requests.
     *
     * @return array
     */
    public function getPostParams(): array
    {
        if ('POST' === $this->getMethod()) {
            return $this->getParams();
        }

        return [];
    }

    /**
     * Generate and return the URL for this request.
     *
     * @throws MadBitSDKException
     *
     * @return string
     */
    public function getUrl(): string
    {
        $this->validateMethod();

        $endpoint = MadBitUrlManipulator::forceSlashPrefix($this->getEndpoint());

        $url = $endpoint;

        if ('POST' !== $this->getMethod()) {
            $params = $this->getParams();
            $url = MadBitUrlManipulator::appendParamsToUrl($url, $params);
        }

        return $url;
    }

    /**
     * Return the default headers that every request should use.
     *
     * @return array
     */
    public static function getDefaultHeaders(): array
    {
        return [
            'User-Agent' => 'madbit-php-'.MadBitSDK::VERSION,
            'Accept-Encoding' => '*',
        ];
    }
}
