<?php

namespace MadBit\SDK\OAuth\Provider;

use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Tool\BearerAuthorizationTrait;
use Psr\Http\Message\ResponseInterface;

class MadBitProvider extends AbstractProvider
{
    use BearerAuthorizationTrait;

    protected $domain = '';

    protected $apiDomain = '';

    public function getBaseAuthorizationUrl()
    {
        return $this->domain . '/login/oauth/authorize';
    }

    public function getBaseAccessTokenUrl(array $params)
    {
        return $this->domain . '/login/oauth/authorize';
    }

    public function getApiDomain()
    {
        return $this->apiDomain;
    }

    public function getResourceOwnerDetailsUrl(AccessToken $token)
    {
        return $this->apiDomain . '/user';
    }

    protected function getDefaultScopes()
    {
        return [];
    }

    protected function checkResponse(ResponseInterface $response, $data)
    {
        if ($response->getStatusCode() >= 400) {
            throw MadBitIdentityProviderException::clientException($response, $data);
        } elseif (isset($data['error'])) {
            throw MadBitIdentityProviderException::oauthException($response, $data);
        }
    }

    protected function createResourceOwner(array $response, AccessToken $token)
    {
        return new MadBitResourceOwner($response);
    }
}
