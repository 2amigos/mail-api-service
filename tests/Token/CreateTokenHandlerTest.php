<?php
namespace App\Application\Token;

use PHPUnit\Framework\TestCase;

class CreateTokenHandlerTest extends TestCase
{

    public function setUp()
    {
        putenv('JWT_SECRET=test');
    }

    public function testShouldReturnAValidToken(): void
    {
        $config = [
            'php.auth.user' => null,
            'requested.scopes' => [],
            'lifespan' => 'now +1 hora'
        ];

        $expiresAt = new \DateTimeImmutable($config['lifespan']);

        $command = new CreateTokenCommand($config);

        $data = (new CreateTokenHandler([]))->handle($command);

        $this->assertNotEmpty($data['token']);

        $this->assertEquals($expiresAt->getTimestamp(), $data['expires']);

    }

    public function testShouldThrowACreateTokenException(): void
    {
        $this->expectException(CreateTokenException::class);

        $config = []; // no lifespan

        $command = new CreateTokenCommand($config);

        $data = (new CreateTokenHandler([]))->handle($command);
    }
}
