<?php declare(strict_types=1);

/*
 * This file is part of the 2amigos/mail-service.
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace App\Infrastructure\Slim\Handlers;

use Crell\ApiProblem\ApiProblem;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Slim\Handlers\AbstractError;
use Throwable;

final class ApiErrorHandler extends AbstractError
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * ApiErrorHandler constructor.
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;

        parent::__construct();
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param Throwable $throwable
     * @return ResponseInterface
     */
    public function __invoke(Request $request, Response $response, Throwable $throwable)
    {
        $this->logger->critical($throwable->getMessage());

        $status = $throwable->getCode() ?: 500;

        $problem = new ApiProblem($throwable->getMessage(), 'about:blank');
        $problem->setStatus($status);
        $body = $problem->asJson(true);

        return $response
                ->withStatus($status)
                ->withHeader('Content-Type', 'application/problem+json')
                ->write($body);
    }
}
