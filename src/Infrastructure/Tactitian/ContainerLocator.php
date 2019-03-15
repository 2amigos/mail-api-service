<?php declare(strict_types=1);

/*
 * This file is part of the 2amigos/mail-service.
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace App\Infrastructure\Tactitian;

use League\Tactician\Exception\MissingHandlerException;
use League\Tactician\Handler\Locator\HandlerLocator;
use Slim\Container;

class ContainerLocator implements HandlerLocator
{
    private $container;
    private $commandToKeyMap;

    public function __construct(Container $container, array $commandToKeyMap = [])
    {
        $this->container = $container;
        $this->commandToKeyMap = $commandToKeyMap;
    }

    /**
     * Adds a new mapping.
     *
     * @param string $commandName
     * @param string $key
     *
     */
    public function addMapping($commandName, $key): void
    {
        $this->commandToKeyMap[$commandName] = $key;
    }

    /**
     * @param string $commandName
     * @return mixed|object
     */
    public function getHandlerForCommand($commandName)
    {
        if (!isset($this->commandToKeyMap[$commandName])) {
            throw MissingHandlerException::forCommand($commandName);
        }
        $key = $this->commandToKeyMap[$commandName];

        return $this->container[$key];
    }
}
