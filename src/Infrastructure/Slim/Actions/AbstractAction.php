<?php declare(strict_types=1);

/*
 * This file is part of the 2amigos/mail-service.
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace App\Infrastructure\Slim\Actions;

use Closure;
use League\Fractal\Manager;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item;
use League\Fractal\TransformerAbstract;
use League\Tactician\CommandBus;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Http\Request;
use Slim\Http\Response;

abstract class AbstractAction
{
    /**
     * @var CommandBus
     */
    protected $commandBus;
    /**
     * @var Manager
     */
    private $fractal;

    /**
     * AbstractAction constructor.
     * @param ContainerInterface $ci
     */
    public function __construct(ContainerInterface $ci)
    {
        $this->commandBus = $ci->get('commandBus');
        $this->fractal = $ci->get('fractal');
    }

    /**
     * @param Request|ServerRequestInterface $request
     * @param Response|ResponseInterface $response
     * @param array $args
     * @return mixed
     */
    abstract public function __invoke(Request $request, Response $response, array $args = []);

    /**
     * Render a JSON response
     *
     * @param Response $response Slim App Response
     * @param  mixed $data The data
     * @param  int $status The HTTP status code.
     * @param  int $encodingOptions Json encoding options
     *
     * @return Response
     */
    protected function renderJson(
        Response $response,
        $data,
        int $status = null,
        int $encodingOptions = JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
    ): ResponseInterface {
        return $response->withJson($data, $status, $encodingOptions);
    }

    /**
     * @param $data
     * @param TransformerAbstract|Closure|null $callback
     * @param string|null $namespace
     * @return array
     */
    protected function createItem($data, $callback = null, string $namespace = null): array
    {
        $callback = $callback ?? function ($data) {
            return $data;
        };

        return $this->fractal->createData(new Item($data, $callback, $namespace))->toArray();
    }

    /**
     * @param array $data
     * @param TransformerAbstract|Closure|null $callback
     * @param string|null $namespace
     * @return array
     */
    protected function createCollection(array $data, $callback = null, string $namespace = null): array
    {
        if (null === $callback || !$callback instanceof TransformerAbstract || !is_callable($callback)) {
            $callback = function ($data) {
                return $data;
            };
        }

        return $this->fractal->createData(new Collection($data, $callback, $namespace))->toArray();
    }
}
