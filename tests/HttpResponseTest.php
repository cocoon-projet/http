<?php

use PHPUnit\Framework\TestCase;
use Cocoon\Http\Facades\Response;

class HttpResponseTest extends TestCase
{
    public function testEmptyResponse()
    {
        $response = Response::empty();
        $this->assertSame('', (string) $response->getBody());
        $this->assertSame(204, $response->getStatusCode());
    }
}