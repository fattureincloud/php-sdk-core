<?php

namespace MadBit\SDK\OAuth\Provider;

use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Tool\BearerAuthorizationTrait;
use Psr\Http\Message\ResponseInterface;

class OAuthProvider extends AbstractProvider
{
    use BearerAuthorizationTrait;

    protected $domain = '';

    protected $apiDomain = '';

    public function __construct(array $options = [], array $collaborators = [])
    {
        $this->domain = $options['domain'];
        $this->apiDomain = $options['apiDomain'];
        unset($options['domain'], $options['apiDomain']);

        parent::__construct($options, $collaborators);
    }

    public function getBaseAuthorizationUrl()
    {
        return $this->domain.'/oauth/authorize';
    }

    public function getBaseAccessTokenUrl(array $params)
    {
        return $this->domain.'/oauth/token';
    }

    public function getApiDomain()
    {
        return $this->apiDomain;
    }

    public function getResourceOwnerDetailsUrl(AccessToken $token)
    {
        return $this->apiDomain.'/user/info';
    }

    protected function getDefaultScopes()
    {
        return [];
    }

    protected function checkResponse(ResponseInterface $response, $data)
    {
        if ($response->getStatusCode() >= 400) {
            throw IdentityProviderException::clientException($response, $data);
        }
        if (isset($data['error'])) {
            throw IdentityProviderException::oauthException($response, $data);
        }
    }

    protected function createResourceOwner(array $response, AccessToken $token)
    {
        return new ResourceOwner($response);
    }
}
