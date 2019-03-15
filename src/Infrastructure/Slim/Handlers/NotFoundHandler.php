<?php declare(strict_types=1);

/*
 * This file is part of the 2amigos/mail-service.
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace App\Infrastructure\Slim\Handlers;

use Crell\ApiProblem\ApiProblem;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Handlers\AbstractHandler;

final class NotFoundHandler extends AbstractHandler
{
    public function __invoke(Request $request, Response $response)
    {
        $problem = new ApiProblem(
            'Not found',
            'http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html'
        );
        $problem->setStatus(404);
        $body = $problem->asJson(true);

        return $response
                ->withStatus(404)
                ->withHeader("Content-type", "application/problem+json")
                ->write($body);
    }
}
