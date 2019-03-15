<?php

namespace App\Application\Response;

use PHPUnit\Framework\TestCase;

class UnauthorizedResponseTest extends TestCase
{
    public function testShouldBeProblemJsonAndHaveProperStatusCode(): void
    {
        $response = new UnauthorizedResponse('Dough!');

        $this->assertEquals('application/problem+json', $response->getHeaderLine('Content-type'));

        $this->assertEquals(401, $response->getStatusCode());
    }
}
