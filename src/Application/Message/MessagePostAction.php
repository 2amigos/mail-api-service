<?php declare(strict_types=1);

/*
 * This file is part of the 2amigos/mail-service.
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace App\Application\Message;

use App\Application\Response\InternalServerErrorResponse;
use App\Infrastructure\Slim\Actions\AbstractAction;
use Slim\Http\Request;
use Slim\Http\Response;

final class MessagePostAction extends AbstractAction
{
    /**
     * @inheritdoc
     */
    public function __invoke(Request $request, Response $response, array $args = [])
    {
        try {
            $files = $request->getUploadedFiles();
            $command = new SendMessageCommand($request->getParsedBody(), $files['attachments'] ?? []);
            /**
             * @see CreateTokenHandler::handle()
             * @throws SendMessageException
             */
            $data = $this->commandBus->handle($command);
            $data = $this->createItem($data);
        } catch (SendMessageException $exception) {
            return new InternalServerErrorResponse('Unable to send a message');
        }

        return $this->renderJson($response, $data, 201);
    }
}
