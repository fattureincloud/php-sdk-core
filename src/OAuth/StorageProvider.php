<?php

namespace MadBit\SDK\OAuth;

use League\OAuth2\Client\Token\AccessToken;

abstract class StorageProvider
{
    /**
     * Return the stored access token, or null.
     *
     * @return AccessToken|null
     */
    abstract public function getStoredAccessToken();

    /**
     * Save the access token to storage.
     *
     * @param AccessToken $accessToken
     * @return void
     */
    abstract public function storeAccessToken($accessToken);

    /**
     * Return the stored OAuth state, or null.
     *
     * @return string|null
     */
    abstract public function getState();

    /**
     * Save the OAuth state to storage.
     *
     * @param string $state
     * @return void
     */
    abstract public function storeState($state);
}
