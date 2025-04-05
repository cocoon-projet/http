<?php

use Laminas\Diactoros\Uri;
use PHPUnit\Framework\TestCase;
use Cocoon\Http\Facades\Request;

class HttpRequestTest extends TestCase
{
	protected function setUp(): void
    {
        parent::setUp();
        // Assurer un minimum de variables serveur pour les tests
        $_SERVER['SCRIPT_NAME'] = '/index.php';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['HTTP_HOST'] = 'localhost';
    }

	public function testServerParamsAreArray(): void
    {
        $serverParams = Request::all();
        $this->assertIsArray($serverParams);
        $this->assertNotEmpty($serverParams);
        // Vérifie la présence de paramètres serveur de base
        $this->assertArrayHasKey('SCRIPT_NAME', $serverParams);
        $this->assertArrayHasKey('REQUEST_METHOD', $serverParams);
        $this->assertArrayHasKey('HTTP_HOST', $serverParams);
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

    protected function tearDown(): void
    {
        $_GET = [];
        $_POST = [];
        $_SERVER = [];
        parent::tearDown();
    }
}