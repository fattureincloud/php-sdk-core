<?php

namespace MadBit\SDK\Http;

class RequestBodyUrlEncoded implements RequestBodyInterface
{
    /**
     * @var array the parameters to send with this request
     */
    protected $params = [];

    /**
     * Creates a new ApiUrlEncodedBody entity.
     *
     * @param array $params
     */
    public function __construct(array $params)
    {
        $this->params = $params;
    }

    /**
     * {@inheritdoc}
     */
    public function getBody(): string
    {
        return http_build_query($this->params, null, '&');
    }
}
