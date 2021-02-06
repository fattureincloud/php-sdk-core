<?php

namespace MadBit\SDK\Exceptions;

use MadBit\SDK\Core\MadBitResponse;

class MadBitResponseException extends MadBitSDKException
{
    /**
     * @var MadBitResponse the response that threw the exception
     */
    protected $response;

    /**
     * @var array decoded response
     */
    protected $responseData;

    /**
     * Creates a MadBitResponseException.
     *
     * @param MadBitResponse     $response          the response that threw the exception
     * @param MadBitSDKException $previousException the more detailed exception
     */
    public function __construct(MadBitResponse $response, MadBitSDKException $previousException = null)
    {
        $this->response = $response;
        $this->responseData = $response->getDecodedBody();

        $errorMessage = $this->get('message', 'Unknown error from API.');
        $errorCode = $this->get('code', -1);

        parent::__construct($errorMessage, $errorCode, $previousException);
    }

    /**
     * A factory for creating the appropriate exception based on the response from API.
     *
     * @param MadBitResponse $response the response that threw the exception
     *
     * @return MadBitResponseException
     */
    public static function create(MadBitResponse $response): MadBitResponseException
    {
        $data = $response->getDecodedBody();

        if (!isset($data['error']['code']) && isset($data['code'])) {
            $data = ['error' => $data];
        }

        $code = isset($data['error']['code']) ? $data['error']['code'] : null;
        $message = isset($data['error']['message']) ? $data['error']['message'] : 'Unknown error from API.';

        if (isset($data['error']['error_subcode'])) {
            switch ($data['error']['error_subcode']) {
                // Other authentication issues
                case 458:
                case 459:
                case 460:
                case 463:
                case 464:
                case 467:
                    return new static($response, new MadBitAuthenticationException($message, $code));
                // Video upload resumable error
                case 1363030:
                case 1363019:
                case 1363033:
                case 1363021:
                case 1363041:
                    return new static($response, new MadBitResumableUploadException($message, $code));

                case 1363037:
                    $previousException = new MadBitResumableUploadException($message, $code);

                    $startOffset = isset($data['error']['error_data']['start_offset']) ? (int) $data['error']['error_data']['start_offset'] : null;
                    $previousException->setStartOffset($startOffset);

                    $endOffset = isset($data['error']['error_data']['end_offset']) ? (int) $data['error']['error_data']['end_offset'] : null;
                    $previousException->setEndOffset($endOffset);

                    return new static($response, $previousException);
            }
        }

        switch ($code) {
            // Login status or token expired, revoked, or invalid
            case 100:
            case 102:
            case 190:
                return new static($response, new MadBitAuthenticationException($message, $code));
            // Server issue, possible downtime
            case 1:
            case 2:
                return new static($response, new MadBitServerException($message, $code));
            // API Throttling
            case 4:
            case 17:
            case 32:
            case 341:
            case 613:
                return new static($response, new MadBitThrottleException($message, $code));
            // Duplicate Post
            case 506:
                return new static($response, new MadBitClientException($message, $code));
        }

        // Missing Permissions
        if (10 == $code || ($code >= 200 && $code <= 299)) {
            return new static($response, new MadBitAuthorizationException($message, $code));
        }

        // OAuth authentication error
        if (isset($data['error']['type']) && 'OAuthException' === $data['error']['type']) {
            return new static($response, new MadBitAuthenticationException($message, $code));
        }

        // All others
        return new static($response, new MadBitOtherException($message, $code));
    }

    /**
     * Returns the HTTP status code.
     *
     * @return int
     */
    public function getHttpStatusCode(): int
    {
        return $this->response->getHttpStatusCode();
    }

    /**
     * Returns the sub-error code.
     *
     * @return int
     */
    public function getSubErrorCode(): int
    {
        return $this->get('error_subcode', -1);
    }

    /**
     * Returns the error type.
     *
     * @return string
     */
    public function getErrorType(): string
    {
        return $this->get('type', '');
    }

    /**
     * Returns the raw response used to create the exception.
     *
     * @return string
     */
    public function getRawResponse(): string
    {
        return $this->response->getBody();
    }

    /**
     * Returns the decoded response used to create the exception.
     *
     * @return array
     */
    public function getResponseData(): array
    {
        return $this->responseData;
    }

    /**
     * Returns the response entity used to create the exception.
     *
     * @return MadBitResponse
     */
    public function getResponse(): MadBitResponse
    {
        return $this->response;
    }

    /**
     * Checks isset and returns that or a default value.
     *
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    private function get(string $key, $default = null)
    {
        if (isset($this->responseData['error'][$key])) {
            return $this->responseData['error'][$key];
        }

        return $default;
    }
}
