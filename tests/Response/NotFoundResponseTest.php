<?php

namespace App\Application\Response;

use PHPUnit\Framework\TestCase;

class NotFoundResponseTest extends TestCase
{
    public function testShouldBeProblemJsonAndHaveProperStatusCode(): void
    {
        $response = new NotFoundResponse('Dough!');

        $this->assertEquals('application/problem+json', $response->getHeaderLine('Content-Type'));

        $this->assertEquals(404, $response->getStatusCode());
    }
}
