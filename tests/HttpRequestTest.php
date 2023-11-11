<?php

use PHPUnit\Framework\TestCase;
use Cocoon\Http\Facades\Request;

class HttpRequestTest extends TestCase
{
	public function testAllServerParamsAreEmpty(): void
    {
        $this->assertEmpty(Request::all());
    }

    public function testRequestGetwithMethodIsGet(): void
    {
        $this->assertSame('GET', Request::get()->getMethod());
    }
}