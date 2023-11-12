<?php

use Laminas\Diactoros\Uri;
use PHPUnit\Framework\TestCase;
use Cocoon\Http\Facades\Request;

class HttpRequestTest extends TestCase
{
	public function testAllServerParamsAreEmpty(): void
    {
        $this->assertEmpty(Request::all());
        $this->assertIsArray(Request::all());
    }

    public function testRequestGetwithMethodIsGet(): void
    {
        $this->assertSame('GET', Request::get()->getMethod());
    }

    public function testRequestQueryData(): void
    {
        $_GET = ['name' => 'Doe', 'surname' => 'John'];
        $this->assertSame('Doe', Request::query('name'));
    }

    public function testRequestOnlyData(): void
    {
        $_POST = ['name' => 'Doe', 'surname' => 'John'];
        $this->assertSame(['surname' => 'John'], Request::only(['surname']));
        $this->assertEquals('John', Request::input('surname'));
    }
}