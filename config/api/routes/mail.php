<?php

/*
 * This file is part of the 2amigos/mail-service.
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use App\Application\Message\MessagePostAction;

$app->group('/mail', function () {

    $this->post('/send', MessagePostAction::class)
        ->setArguments([
            'scopes' => [
                'mail.all',
                'mail.send',
            ],
            'input_filter' => 'mail_send',
        ]);
});

