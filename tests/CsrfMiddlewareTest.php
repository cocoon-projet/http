<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Cocoon\Http\Middleware\CsrfMiddleware;
use Cocoon\Http\Facades\Session;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\UriInterface;

class CsrfMiddlewareTest extends TestCase
{
    private CsrfMiddleware $middleware;
    private $request;
    private $handler;
    private $response;
    private $uri;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Initialiser le middleware
        $this->middleware = new CsrfMiddleware([
            '#^/api/webhook#', // Exemple d'URL exclue
            '#^/api/external#'
        ]);

        // Mock des objets nécessaires
        $this->request = $this->createMock(ServerRequestInterface::class);
        $this->handler = $this->createMock(RequestHandlerInterface::class);
        $this->response = $this->createMock(ResponseInterface::class);
        $this->uri = $this->createMock(UriInterface::class);

        // Configuration par défaut du handler
        $this->handler->method('handle')
            ->willReturn($this->response);
    }

    /**
     * Test d'une requête GET (non protégée)
     */
    public function testGetRequestPassesWithoutToken(): void
    {
        // Configurer la requête GET
        $this->request->method('getMethod')
            ->willReturn('GET');

        $response = $this->middleware->process($this->request, $this->handler);
        
        $this->assertSame($this->response, $response);
    }

    /**
     * Test d'une requête POST avec un token valide
     */
    public function testPostRequestWithValidToken(): void
    {
        // Simuler une session avec un token valide
        $token = bin2hex(random_bytes(32));
        Session::set('token', [[
            'value' => $token,
            'expires' => time() + 3600
        ]]);

        // Configurer l'URI
        $this->uri->method('getPath')
            ->willReturn('/submit');
        
        // Configurer la requête POST
        $this->request->method('getMethod')
            ->willReturn('POST');
        $this->request->method('getParsedBody')
            ->willReturn(['_token' => $token]);
        $this->request->method('getUri')
            ->willReturn($this->uri);

        $response = $this->middleware->process($this->request, $this->handler);
        
        $this->assertSame($this->response, $response);
    }

    /**
     * Test d'une requête POST avec un token invalide
     */
    public function testPostRequestWithInvalidToken(): void
    {
        $this->expectException(\Exception::class);

        // Configurer l'URI
        $this->uri->method('getPath')
            ->willReturn('/submit');

        // Configurer la requête POST avec un token invalide
        $this->request->method('getMethod')
            ->willReturn('POST');
        $this->request->method('getParsedBody')
            ->willReturn(['_token' => 'invalid_token']);
        $this->request->method('getUri')
            ->willReturn($this->uri);

        $this->middleware->process($this->request, $this->handler);
    }

    /**
     * Test d'une URL exclue
     */
    public function testExcludedUrlPassesWithoutToken(): void
    {
        // Configurer l'URI pour une URL exclue
        $this->uri->method('getPath')
            ->willReturn('/api/webhook/callback');
        
        // Configurer la requête POST pour une URL exclue
        $this->request->method('getMethod')
            ->willReturn('POST');
        $this->request->method('getUri')
            ->willReturn($this->uri);

        $response = $this->middleware->process($this->request, $this->handler);
        
        $this->assertSame($this->response, $response);
    }

    /**
     * Test d'une requête AJAX avec token dans l'en-tête
     */
    public function testAjaxRequestWithHeaderToken(): void
    {
        // Simuler une session avec un token valide
        $token = bin2hex(random_bytes(32));
        Session::set('token', [[
            'value' => $token,
            'expires' => time() + 3600
        ]]);

        // Configurer l'URI
        $this->uri->method('getPath')
            ->willReturn('/api/data');

        // Configurer la requête POST avec token dans l'en-tête
        $this->request->method('getMethod')
            ->willReturn('POST');
        $this->request->method('getParsedBody')
            ->willReturn([]);
        $this->request->method('getHeaderLine')
            ->with('X-CSRF-TOKEN')
            ->willReturn($token);
        $this->request->method('getUri')
            ->willReturn($this->uri);

        $response = $this->middleware->process($this->request, $this->handler);
        
        $this->assertSame($this->response, $response);
    }
} 