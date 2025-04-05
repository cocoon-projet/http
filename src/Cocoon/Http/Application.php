<?php

namespace Cocoon\Http;

use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;

class Application implements RequestHandlerInterface
{
    private array $middlewares = [];
    private int $index = 0;

    public function add(MiddlewareInterface $middleware): self
    {
        $this->middlewares[] = $middleware;
        return $this;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if (!isset($this->middlewares[$this->index])) {
            throw new \RuntimeException('No middleware available');
        }

        $middleware = $this->middlewares[$this->index];
        $this->index++;
        
        return $middleware->process($request, $this);
    }

    public function run(ServerRequestInterface $request = null): ResponseInterface
    {
        return $this->handle($request);
    }
} 