<?php

namespace App\Application\Response;

use PHPUnit\Framework\TestCase;

class PreconditionFailedResponseTest extends TestCase
{
    public function testShouldBeProblemJsonAndHaveProperStatusCode(): void
    {
        $response = new PreconditionFailedResponse('Dough!');
        $this->assertEquals('application/problem+json', $response->getHeaderLine('Content-type'));
        $this->assertEquals(412, $response->getStatusCode());
    }
}
