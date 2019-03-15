<?php declare(strict_types=1);

/*
 * This file is part of the 2amigos/mail-service.
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace App\Application\Token;

use DateTimeImmutable;
use Exception;
use Firebase\JWT\JWT;
use Tuupola\Base62;

class CreateTokenHandler
{
    /**
     * @var array
     */
    private $scopes;

    /**
     * CreateTokenHandler constructor.
     * @param array $scopes
     */
    public function __construct(array $scopes)
    {
        $this->scopes = $scopes;
    }

    /**
     * @param CreateTokenCommand $command
     * @throws CreateTokenException
     * @return array
     */
    public function handle(CreateTokenCommand $command): array
    {
        $data = [];

        try {
            $config = $command->getConfiguration();
            $existing = $this->scopes;

            $scopes = array_filter(
                $config['requested.scopes'] ?? [],
                function ($needle) use ($existing) {
                    return in_array($needle, $existing, false);
                }
            );

            $now = new DateTimeImmutable('now');
            $future = new DateTimeImmutable($config['lifespan']);
            $sub = $config['php.auth.user'] ?? null;
            $jti = (new Base62())->encode(random_bytes(16));

            $payload = [
                'iat' => $now->getTimestamp(),
                'exp' => $future->getTimestamp(),
                'jti' => $jti,
                'sub' => $sub,
                'scope' => $scopes,
            ];

            $secret = getenv('JWT_SECRET');
            $token = JWT::encode($payload, $secret);

            $data['token'] = $token;
            $data['expires'] = $future->getTimestamp();
        } catch (Exception $e) {
            throw new CreateTokenException($e->getMessage(), $e->getCode(), $e->getPrevious());
        }

        return $data;
    }
}
