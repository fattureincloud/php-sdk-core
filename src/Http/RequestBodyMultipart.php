<?php

namespace MadBit\SDK\Http;

use MadBit\SDK\FileUpload\MadBitFile;

class RequestBodyMultipart implements RequestBodyInterface
{
    /**
     * @var string the boundary
     */
    private $boundary;

    /**
     * @var array the parameters to send with this request
     */
    private $params;

    /**
     * @var array the files to send with this request
     */
    private $files = [];

    /**
     * @param array  $params   the parameters to send with this request
     * @param array  $files    the files to send with this request
     * @param string $boundary provide a specific boundary
     */
    public function __construct(array $params = [], array $files = [], $boundary = null)
    {
        $this->params = $params;
        $this->files = $files;
        $this->boundary = $boundary ?: uniqid();
    }

    /**
     * {@inheritdoc}
     */
    public function getBody(): string
    {
        $body = '';

        // Compile normal params
        $params = $this->getNestedParams($this->params);
        foreach ($params as $k => $v) {
            $body .= $this->getParamString($k, $v);
        }

        // Compile files
        foreach ($this->files as $k => $v) {
            $body .= $this->getFileString($k, $v);
        }

        // Peace out
        $body .= "--{$this->boundary}--\r\n";

        return $body;
    }

    /**
     * Get the boundary.
     *
     * @return string
     */
    public function getBoundary(): string
    {
        return $this->boundary;
    }

    /**
     * Get the headers needed before transferring the content of a POST file.
     *
     * @param MadBitFile $file
     *
     * @return string
     */
    protected function getFileHeaders(MadBitFile $file): string
    {
        return "\r\nContent-Type: {$file->getMimetype()}";
    }

    /**
     * Get the string needed to transfer a file.
     *
     * @param string     $name
     * @param MadBitFile $file
     *
     * @return string
     */
    private function getFileString(string $name, MadBitFile $file): string
    {
        return sprintf(
            "--%s\r\nContent-Disposition: form-data; name=\"%s\"; filename=\"%s\"%s\r\n\r\n%s\r\n",
            $this->boundary,
            $name,
            $file->getFileName(),
            $this->getFileHeaders($file),
            $file->getContents()
        );
    }

    /**
     * Get the string needed to transfer a POST field.
     *
     * @param string $name
     * @param string $value
     *
     * @return string
     */
    private function getParamString(string $name, string $value): string
    {
        return sprintf(
            "--%s\r\nContent-Disposition: form-data; name=\"%s\"\r\n\r\n%s\r\n",
            $this->boundary,
            $name,
            $value
        );
    }

    /**
     * Returns the params as an array of nested params.
     *
     * @param array $params
     *
     * @return array
     */
    private function getNestedParams(array $params): array
    {
        $query = http_build_query($params, null, '&');
        $params = explode('&', $query);
        $result = [];

        foreach ($params as $param) {
            list($key, $value) = explode('=', $param, 2);
            $result[urldecode($key)] = urldecode($value);
        }

        return $result;
    }
}
