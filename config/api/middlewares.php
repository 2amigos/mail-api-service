<?php

/*
 * This file is part of the 2amigos/mail-service.
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use App\Application\Response\UnauthorizedResponse;
use App\Domain\Token\Token;
use Gofabian\Negotiation\NegotiationMiddleware;
use Tuupola\Middleware\CorsMiddleware;
use Tuupola\Middleware\HttpBasicAuthentication;
use Tuupola\Middleware\JwtAuthentication;
use App\Application\Middleware\ScopeMiddleware;
use App\Application\Middleware\InputFilterMiddleware;

$container = $app->getContainer();

$container['HttpBasicAuthentication'] = function () {
    return new HttpBasicAuthentication([
        'path' => '/token',
        'relaxed' => ['127.0.0.1', 'localhost'],
        'error' => function ($request, $response, $arguments) {
            return new UnauthorizedResponse($arguments['message'], 401);
        },
        'users' => [
            'test' => 'test',
        ],
    ]);
};

$container['token'] = function () {
    return new Token;
};

$container['JwtAuthentication'] = function ($container) {
    return new JwtAuthentication([
        'path' => '/',
        'ignore' => ['/token'],
        'secret' => getenv('JWT_SECRET'),
        'logger' => $container['logger'],
        'attribute' => false,
        'relaxed' => ['127.0.0.1', 'localhost'],
        'error' => function ($response, $arguments) {
            return new UnauthorizedResponse($arguments['message'], 401);
        },
        'before' => function ($request, $arguments) use ($container) {
            $container['token']->populate($arguments['decoded']);
        },
    ]);
};

$container['ScopeMiddleware'] = function ($container) {
    return new ScopeMiddleware([
        'path' => '/',
        'ignore' => ['/token'],
    ],
        $container['token']
    );
};

$container['InputFilterMiddleware'] = function ($container) {
    return new InputFilterMiddleware([
        'path' => '/',
        'ignore' => ['/token'],
        'specs' => $container['settings']['input_filter_specs']
    ]);
};

$container['CorsMiddleware'] = function ($container) {
    return new CorsMiddleware([
        'logger' => $container['logger'],
        'origin' => ['*'],
        'methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'],
        'headers.allow' => ['Authorization', 'If-Match', 'If-Unmodified-Since'],
        'headers.expose' => ['Authorization', 'Etag'],
        'credentials' => true,
        'cache' => 60,
        'error' => function ($request, $response, $arguments) {
            return new UnauthorizedResponse($arguments['message'], 401);
        },
    ]);
};

$container['NegotiationMiddleware'] = function () {
    return new NegotiationMiddleware([
        'accept' => ['application/json', 'application/form-data'],
    ]);
};

$app->add('HttpBasicAuthentication');
$app->add('InputFilterMiddleware');
$app->add('ScopeMiddleware');
$app->add('JwtAuthentication');
$app->add('CorsMiddleware');
$app->add('NegotiationMiddleware');
