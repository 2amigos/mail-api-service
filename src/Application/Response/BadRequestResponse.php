<?php declare(strict_types=1);

/*
 * This file is part of the 2amigos/mail-service.
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace App\Application\Response;

use Crell\ApiProblem\ApiProblem;
use Slim\Http\Headers;
use Slim\Http\Response;
use Slim\Http\Stream;

class BadRequestResponse extends Response
{
    /**
     * @inheritdoc
     */
    public function __construct($message, $status = 400)
    {
        $problem = new ApiProblem($message, 'https://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html#sec10.4.1');
        $problem->setStatus($status);

        $handle = fopen('php://temp', 'wb+');
        $body = new Stream($handle);
        $body->write($problem->asJson(true));
        $headers = new Headers();
        $headers->set('Content-Type', 'application/problem+json');
        parent::__construct($status, $headers, $body);
    }
}
