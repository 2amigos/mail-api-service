<?php
/**
 * Created by PhpStorm.
 * User: tonydspaniard
 * Date: 2019-02-13
 * Time: 12:34
 */

namespace App\Application\Response;


use PHPUnit\Framework\TestCase;

class InternalServerErrorResponseTest extends TestCase
{
    public function testShouldBeProblemJsonAndHaveProperStatusCode(): void
    {
        $response = new InternalServerErrorResponse('Dough!');

        $this->assertEquals('application/problem+json', $response->getHeaderLine('Content-Type'));

        $this->assertEquals(501, $response->getStatusCode());
    }
}
