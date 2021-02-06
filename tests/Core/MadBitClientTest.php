<?php

namespace MadBit\SDK\Test\Core;

use GuzzleHttp\Exception\ClientException;
use MadBit\SDK\Core\MadBitClientOld;
use MadBit\SDK\OAuth\Provider\IdentityProviderException;
use PHPUnit\Framework\TestCase;
use Throwable;
use TypeError;

class MadBitClientTest extends TestCase
{
    private function createFICClient()
    {
        return new FICClientOld('12345', '67890', 'https://oauth-test.fattureincloud.it');
    }

    public function test__construct()
    {
        $client = $this->createFICClient();
        $this->assertInstanceOf(
            MadBitClientOld::class,
            $client
        );
    }

    public function testGetAuthorizationParams()
    {
        $client = $this->createFICClient();
        $params = $client->getAuthorizationParams();
        $expectedUrl = "https://api-v2.fattureincloud.it/oauth/authorize?state={$params['state']}&scope=&response_type=code&approval_prompt=auto&redirect_uri=https%3A%2F%2Foauth-test.fattureincloud.it&client_id=12345";
        $this->assertEquals($expectedUrl, $params['authorization_url']);
    }

    public function testGetAccessTokenWithWrongAuthCode()
    {
        $client = $this->createFICClient();
        $this->expectException(IdentityProviderException::class);
        $client->getAccessToken('00000');
    }

    public function testGetResourceOwnerWithoutAuthentication()
    {
        $client = $this->createFICClient();
        $this->expectException(TypeError::class);
        $client->getResourceOwner();
    }

    public function testExecuteAuthenticatedRequestWithoutAuthentication()
    {
        $client = $this->createFICClient();
        try {
            $client->executeAuthenticatedRequest('GET', 'user/info');
        } catch (ClientException $e) {
            $this->assertEquals(401, $e->getCode());
        }
    }
}
