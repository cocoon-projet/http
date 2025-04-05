<?php

use PHPUnit\Framework\TestCase;
use Cocoon\Http\Facades\Response;
use Psr\Http\Message\ResponseInterface;

class HttpResponseTest extends TestCase
{
    public function testEmptyResponse()
    {
        $response = Response::empty();
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame('', (string) $response->getBody());
        $this->assertSame(204, $response->getStatusCode());
    }

    public function testTextResponse()
    {
        $body = 'je suis reponse text';
        $response = Response::text($body);
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame($body, (string) $response->getBody());
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testHtmlResponse()
    {
        $body = '<html><title>html response</title></html>';
        $response = Response::html($body);
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame($body, (string) $response->getBody());
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testXmlResponse()
    {
        $xml = '<test>xml response</test>';
        $response = Response::xml($xml);
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame('application/xml; charset=utf-8', $response->getHeaderLine('content-type'));
        $this->assertSame($xml, (string) $response->getBody());
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testJsonResponse()
    {
        $data = [
            'body' => [
                'json' => [
                    'data',
                ],
            ],
        ];
        $json = '{"body":{"json":["data"]}}';
        $response = Response::json($data);
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame($json, (string) $response->getBody());
        $this->assertSame('application/json', $response->getHeaderLine('content-type'));
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testRedirectResponse()
    {
        $response = Response::redirect('/john/doe');
        $this->assertSame(302, $response->getStatusCode());
        $this->assertTrue($response->hasHeader('Location'));
        $this->assertSame('/john/doe', $response->getHeaderLine('Location'));
    }
}