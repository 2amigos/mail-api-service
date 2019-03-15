<?php

/*
 * This file is part of the 2amigos/mail-service.
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use Zend\InputFilter\ArrayInput;
use Zend\Validator\Callback;

if (file_exists(__DIR__ . '/settings-env.php')) {
    $override = require __DIR__ . '/settings-env.php';
} elseif (file_exists(__DIR__ . '/settings-local.php')) {
    $override = require __DIR__ . '/settings-local.php';
} elseif (file_exists(__DIR__ . '/env/' . getenv('APP_ENV') . '/settings.php')) {
    $override = require __DIR__ . '/env/' . getenv('APP_ENV') . '/settings.php';
} else {
    $override = [];
}

const APP_PROJECT_ROOT = __DIR__ . '/../../';

return array_merge(
    [
        'determineRouteBeforeAppMiddleware' => true,

        'displayErrorDetails' => true,

        'scopes' => [
            'mail.all',
            'mail.send',
        ],

        'mustache' => [
            'i18n' => APP_PROJECT_ROOT . 'i18n/%s/messages.php',
        ],

        'token' => [
            'lifespan' => 'now +2 hours',
        ],

        'mailer' => [
            'attachments' => APP_PROJECT_ROOT . 'runtime/mail/attachments',
            'spool' => APP_PROJECT_ROOT . 'runtime/spool/default',
            'queue' => [
                'name' => 'enqueue.app.mail',
                'path' => APP_PROJECT_ROOT . 'runtime/queue',
                'pre_fetch_count' => 1, // default value
                'polling_interval' => 100 // default value 100 ms
            ]
        ],

        'views' => APP_PROJECT_ROOT . 'views',

        'input_filter_specs' => [
            'mail_send' => [
                [
                    'name' => 'template',
                    'required' => true,
                    'filters' => [
                        ['name' => 'StringTrim'],
                    ],
                    'validators' => [
                        [
                            'name' => 'Callback',
                            'options' => [
                                'messages' => [
                                    Callback::INVALID_VALUE => 'Unknown template',
                                ],
                                'callback' => function (string $value) {
                                    return is_file(APP_PROJECT_ROOT . 'views/html/' . $value . '.mustache')
                                           || is_file(APP_PROJECT_ROOT . 'views/txt/' . $value . '.mustache');
                                },
                            ],
                        ],
                    ],
                ],
                [
                    'name' => 'language',
                    'required' => false,
                    'filters' => [
                        ['name' => 'StringTrim'],
                    ],
                ],
                [
                    'name' => 'from',
                    'required' => false, // not required (if false we will use settings from)
                    'filters' => [
                        ['name' => 'StringTrim'],
                        [
                            'name' => 'Callback',
                            'options' => [
                                'callback' => function (?string $value) {
                                    return $value ?? [getenv('MAIL_NO_REPLY_EMAIL') => getenv('APP_NAME')];
                                },
                            ],
                        ],
                    ],
                    'validators' => [
                        [
                            'name' => 'EmailAddress',
                        ],
                    ],
                ],
                [
                    'name' => 'to',
                    'required' => true,
                    'filters' => [
                        ['name' => 'StringTrim'],
                    ],
                    'validators' => [
                        ['name' => 'EmailAddress'],
                    ],
                ],
                [
                    'name' => 'subject',
                    'required' => true,
                    'filters' => [
                        ['name' => 'StringTrim'],
                    ],
                ],
                [
                    'name' => 'data',
                    'type' => ArrayInput::class,
                    'required' => false,
                    'allow_empty' => true,
                ],
            ],
        ],
    ],
    $override
);
