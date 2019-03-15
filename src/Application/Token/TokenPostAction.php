<?php declare(strict_types=1);

/*
 * This file is part of the 2amigos/mail-service.
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace App\Application\Token;

use App\Application\Response\InternalServerErrorResponse;
use App\Infrastructure\Slim\Actions\AbstractAction;
use League\Fractal\Resource\Item;
use Psr\Container\ContainerInterface;
use Slim\Http\Request;
use Slim\Http\Response;

final class TokenPostAction extends AbstractAction
{
    /**
     * @var array
     */
    private $config;

    /**
     * TokenPostAction constructor.
     * @param ContainerInterface $ci
     */
    public function __construct(ContainerInterface $ci)
    {
        $this->config = (array)$ci['settings']['token'];

        parent::__construct($ci);
    }

    /**
     * @inheritdoc
     */
    public function __invoke(Request $request, Response $response, array $args = [])
    {
        try {
            $this->config['php.auth.user'] = $request->getServerParam('PHP_AUTH_USER');
            $this->config['requested.scopes'] = $request->getParsedBody();

            $command = new CreateTokenCommand($this->config);
            /**
             * @see CreateTokenHandler::handle()
             * @throws CreateTokenException
             */
            $data = $this->commandBus->handle($command);

            $data = $this->createItem($data);
        } catch (CreateTokenException $exception) {
            return new InternalServerErrorResponse('Unable to create jwt token');
        }

        return $this->renderJson($response, $data, 201);
    }
}
