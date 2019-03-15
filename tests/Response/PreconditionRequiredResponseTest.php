<?php

namespace App\Application\Response;

use PHPUnit\Framework\TestCase;

class PreconditionRequiredResponseTest extends TestCase
{
    public function testShouldBeProblemJsonAndHaveProperStatusCode(): void
    {
        $response = new PreconditionRequiredResponse('Dough!');

        $this->assertEquals('application/problem+json', $response->getHeaderLine('Content-type'));

        $this->assertEquals(428, $response->getStatusCode());
    }
}
