<?php

namespace MadBit\SDK\Test\Core;

use MadBit\SDK\Core\MadBitClient;
use PHPUnit\Framework\TestCase;

class MadBitClientTest extends TestCase
{
    public function test__construct()
    {
        $client = new FICClient('12345', '67890', 'https://oauth-test.fattureincloud.it');
        $this->assertInstanceOf(
            MadBitClient::class,
            $client
        );
    }

    public function testGetAccessToken()
    {
    }

    public function testExecuteAuthenticatedRequest()
    {
    }

    public function testGetAuthorizationParams()
    {
    }

    public function testGetResourceOwner()
    {
    }
}
