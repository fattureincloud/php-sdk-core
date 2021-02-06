<?php

namespace MadBit\SDK\Http;

class ApiRawResponse
{
    /**
     * @var array the response headers in the form of an associative array
     */
    protected $headers;

    /**
     * @var string the raw response body
     */
    protected $body;

    /**
     * @var int the HTTP status response code
     */
    protected $httpResponseCode;

    /**
     * Creates a new ApiRawResponse entity.
     *
     * @param array|string $headers        the headers as a raw string or array
     * @param string       $body           the raw response body
     * @param int          $httpStatusCode the HTTP response code (if sending headers as parsed array)
     */
    public function __construct($headers, string $body, $httpStatusCode = null)
    {
        if (is_numeric($httpStatusCode)) {
            $this->httpResponseCode = (int) $httpStatusCode;
        }

        if (is_array($headers)) {
            $this->headers = $headers;
        } else {
            $this->setHeadersFromString($headers);
        }

        $this->body = $body;
    }

    /**
     * Return the response headers.
     *
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Return the body of the response.
     *
     * @return string
     */
    public function getBody(): string
    {
        return $this->body;
    }

    /**
     * Return the HTTP response code.
     *
     * @return int
     */
    public function getHttpResponseCode(): int
    {
        return $this->httpResponseCode;
    }

    /**
     * Sets the HTTP response code from a raw header.
     *
     * @param string $rawResponseHeader
     */
    public function setHttpResponseCodeFromHeader(string $rawResponseHeader)
    {
        // https://tools.ietf.org/html/rfc7230#section-3.1.2
        list(, $status) = array_pad(explode(' ', $rawResponseHeader, 3), 3, null);
        $this->httpResponseCode = (int) $status;
    }

    /**
     * Parse the raw headers and set as an array.
     *
     * @param string $rawHeaders the raw headers from the response
     */
    protected function setHeadersFromString(string $rawHeaders)
    {
        // Normalize line breaks
        $rawHeaders = str_replace("\r\n", "\n", $rawHeaders);

        // There will be multiple headers if a 301 was followed
        // or a proxy was followed, etc
        $headerCollection = explode("\n\n", trim($rawHeaders));
        // We just want the last response (at the end)
        $rawHeader = array_pop($headerCollection);

        $headerComponents = explode("\n", $rawHeader);
        foreach ($headerComponents as $line) {
            if (false === strpos($line, ': ')) {
                $this->setHttpResponseCodeFromHeader($line);
            } else {
                list($key, $value) = explode(': ', $line, 2);
                $this->headers[$key] = $value;
            }
        }
    }
}
