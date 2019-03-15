<?php

/*
 * This file is part of the 2amigos/mail-service.
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use Slim\App;
use Symfony\Component\Dotenv\Dotenv;

if (!getenv('APP_ENV')) {
    if (!class_exists(Dotenv::class)) {
        throw new \RuntimeException('APP_ENV environment variable is not defined. You need to define environment variables for configuration or add "symfony/dotenv" as a Composer dependency to load variables from a .env file.');
    }

    (new Dotenv())->load(__DIR__ . '/../../.env');
}

$settings = require __DIR__ . '/settings.php';

$app = new App(['settings' => $settings]);

$container = $app->getContainer();

require __DIR__ . '/dependencies.php';

require __DIR__ . '/middlewares.php';

require __DIR__ . '/routes/token.php';

require __DIR__ . '/routes/mail.php';

$app->run();
