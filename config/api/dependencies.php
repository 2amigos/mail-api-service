<?php

/*
 * This file is part of the 2amigos/mail-service.
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use App\Application\Message\SendMessageCommand;
use App\Application\Message\SendMessageFsQueueHandler;
use App\Application\Message\SendMessageSpoolHandler;
use App\Application\Token\CreateTokenCommand;
use App\Application\Token\CreateTokenHandler;
use App\Infrastructure\Enqueue\Message\FsMessageProducer;
use App\Infrastructure\Mustache\Helpers\TranslatorHelper;
use App\Infrastructure\SwitftMailer\EnqueueSpool;
use App\Infrastructure\Tactitian\ContainerLocator;
use Enqueue\Fs\FsConnectionFactory;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use League\Fractal\Manager;
use League\Fractal\Serializer\DataArraySerializer;
use League\Tactician\CommandBus;
use League\Tactician\Handler\CommandHandlerMiddleware;
use League\Tactician\Handler\CommandNameExtractor\ClassNameExtractor;
use League\Tactician\Handler\MethodNameInflector\HandleInflector;
use Micheh\Cache\CacheUtil;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;

$container = $app->getContainer();

$container['token.create.handler'] = function ($container) {
    return new CreateTokenHandler($container['settings']['scopes']);
};

$container['mail.send.spool.handler'] = function ($container) {
    return new SendMessageSpoolHandler(
        $container['mailer'], // use enqueue.mailer if you wish to use enqueue interop (see services.yaml)
        $container['mustache'],
        $container['mustache.i18n.helper'],
        $container['fs']
    );
};

$container['mail.send.queue.handler'] = function ($container) {
    return new SendMessageFsQueueHandler(
        $container['enqueueMessageProducer'],
        $container['mustache'],
        $container['fs']
    );
};

$container['commandBus'] = function ($container) {
    $inflector = new HandleInflector();

    $map = [
        CreateTokenCommand::class => 'token.create.handler',
        SendMessageCommand::class => 'mail.send.spool.handler',
    ];

    $locator = new ContainerLocator($container, $map);

    $nameExtractor = new ClassNameExtractor();

    $commandHandlerMiddleware = new CommandHandlerMiddleware(
        $nameExtractor,
        $locator,
        $inflector
    );

    return new CommandBus([$commandHandlerMiddleware]);
};

$container['fractal'] = function () {
    $serializer = new DataArraySerializer();
    $fractal = new Manager();
    $fractal->setSerializer($serializer);

    return $fractal;
};

$container['mailer'] = function ($container) {

    $spool = new Swift_FileSpool($container['settings']['mailer']['spool']);

    $transport = new Swift_SpoolTransport($spool);

    return new Swift_Mailer($transport);
};

$container['mustache.i18n.helper'] = function ($container) {
    return new TranslatorHelper($container['settings']['mustache']['i18n']);
};

$container['mustache'] = function ($container) {
    $loader = new Mustache_Loader_FilesystemLoader($container['settings']['views']);
    /** @var TranslatorHelper $i18n */
    $i18n = $container['mustache.i18n.helper'];

    return new Mustache_Engine(
        [
            'loader' => $loader,
            'partials_loader' => $loader,
            'helpers' => [
                'i18n' => function ($text) use ($i18n) {
                    return $i18n->get($text); // language should be set in advance!
                },
            ],
        ]
    );
};

$container['fs'] = function ($container) {
    $adapter = new Local($container['settings']['mailer']['attachments']);

    return new Filesystem($adapter);
};

$container['logger'] = function () {
    $logger = new Logger('api.mail');

    $formatter = new LineFormatter(
        '[%datetime%] [%level_name%]: %message% %context%\n',
        null,
        true,
        true
    );

    /* Log to timestamped files */
    $rotating = new RotatingFileHandler(__DIR__ . '/../../runtime/api.log', 0, Logger::DEBUG);
    $rotating->setFormatter($formatter);
    $logger->pushHandler($rotating);

    return $logger;
};

$container['enqueueMessageProducer'] = function ($container) {
    $settings = $container['settings']['mailer']['queue'];
    $queue = $settings['name'];
    unset($settings['name']);

    return new FsMessageProducer($queue, new FsConnectionFactory($settings));
};

/**
 * If you are using Interop Enqueue with SwiftMailer's spool
 */
$container['enqueue.mailer'] = function ($container) {
    $settings = $container['settings']['mailer']['queue'];
    $queue = $settings['name'];
    unset($settings['name']);
    $factory = new FsConnectionFactory($settings);

    $spool = new EnqueueSpool($factory->createContext(), $queue);
    $transport = new Swift_SpoolTransport($spool);

    return new Swift_Mailer($transport);
};

$container['cache'] = function () {
    return new CacheUtil;
};
