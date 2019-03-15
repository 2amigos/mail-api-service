<?php declare(strict_types=1);

/*
 * This file is part of the 2amigos/mail-service.
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace App\Application\Middleware;

use App\Application\Response\ForbiddenResponse;
use App\Domain\Token\Token;
use ArrayAccess;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Tuupola\Middleware\DoublePassTrait;
use Tuupola\Middleware\HttpBasicAuthentication\RequestMethodRule;
use Tuupola\Middleware\HttpBasicAuthentication\RequestPathRule;

final class ScopeMiddleware implements MiddlewareInterface
{
    use DoublePassTrait;

    /**
     * @var \SplStack
     */
    private $rules;
    /**
     * @var Token
     */
    private $token;

    /**
     * ScopeValidationMiddleware constructor.
     * @param array $options
     * @param Token $token
     */
    public function __construct(array $options, Token $token)
    {
        $this->rules = new \SplStack;

        if (!isset($options['rules'])) {
            $this->rules->push(new RequestMethodRule([
                'ignore' => ['OPTIONS'],
            ]));
        }

        if (null !== $options['path'] ?? null) {
            $this->rules->push(new RequestPathRule([
                'path' => $options['path'],
                'ignore' => $options['ignore'] ?? [],
            ]));
        }

        $this->token = $token;
    }

    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (false === $this->shouldProcess($request)) {
            return $handler->handle($request);
        }

        if (false === $this->token->hasScope($this->getScopes($request))) {
            return new ForbiddenResponse('Token not allowed for this route.');
        }

        return $handler->handle($request);
    }

    /**
     * @param ServerRequestInterface $request
     * @return array
     */
    private function getScopes(ServerRequestInterface $request): array
    {
        $route = $request->getAttribute('route');
        if (!empty($route)) {
            $scopes = $route->getArgument('scopes');
            if (\is_array($scopes) || $scopes instanceof ArrayAccess) {
                return $scopes;
            }
        }

        return [];
    }

    /**
     * Test if current request should be authenticated.
     *
     * @param ServerRequestInterface $request
     *
     * @return bool
     */
    private function shouldProcess(ServerRequestInterface $request): bool
    {
        /* If any of the rules in stack return false will not authenticate */
        foreach ($this->rules as $callable) {
            if (false === $callable($request)) {
                return false;
            }
        }

        return true;
    }
}
