<?php

/*
 * This file is part of the 2amigos/mail-service.
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use App\Application\Token\TokenPostAction;

$app->post('/token', TokenPostAction::class);