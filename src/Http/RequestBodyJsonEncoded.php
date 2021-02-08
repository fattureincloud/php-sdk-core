<?php

namespace MadBit\SDK\Http;

class RequestBodyJsonEncoded implements RequestBodyInterface
{
    /**
     * @var array the parameters to send with this request
     */
    protected $params = [];

    /**
     * Creates a new RequestBodyJsonEncodedBody entity.
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
        return json_encode($this->params);
    }
}
