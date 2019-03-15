<?php declare(strict_types=1);

/*
 * This file is part of the 2amigos/mail-service.
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace App\Application\Token;

final class CreateTokenCommand
{
    /**
     * @var array
     */
    private $config;

    /**
     * CreateTokenCommand constructor.
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * @return array
     */
    public function getConfiguration(): array
    {
        return $this->config;
    }
}
