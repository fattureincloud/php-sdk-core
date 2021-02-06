<?php

namespace MadBit\SDK\Core;

use MadBit\SDK\Authentication\AccessToken;
use Serializable;

class MadBitApp implements Serializable
{
    /**
     * @var string the app client ID
     */
    protected $id;

    /**
     * @var string the app client secret
     */
    protected $secret;

    /**
     * @param string      $id
     * @param null|string $secret
     */
    public function __construct(string $id, string $secret = null)
    {
        $this->id = $id;
        $this->secret = $secret;
    }

    /**
     * Returns the app client ID.
     *
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Returns the app client secret.
     *
     * @return string
     */
    public function getSecret(): string
    {
        return $this->secret;
    }

    /**
     * Returns an app access token.
     *
     * @return AccessToken
     */
    public function getAccessToken(): AccessToken
    {
        return new AccessToken($this->id.'|'.$this->secret);
    }

    /**
     * Serializes the MadBitApp entity as a string.
     *
     * @return string
     */
    public function serialize(): string
    {
        return implode('|', [$this->id, $this->secret]);
    }

    /**
     * Unserializes a string as a MadBitApp entity.
     *
     * @param string $serialized
     */
    public function unserialize($serialized)
    {
        list($id, $secret) = explode('|', $serialized);
        if (!$secret) {
            $secret = null;
        }

        $this->__construct($id, $secret);
    }
}
