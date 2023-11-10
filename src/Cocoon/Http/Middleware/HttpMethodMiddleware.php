<?php

namespace Cocoon\Http\Middleware;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Class HttpMethodMiddleware
 * @package Cocoon\Http\Middleware
 */
class HttpMethodMiddleware implements MiddlewareInterface
{
    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler) :ResponseInterface
    {
        if (isset($request->getParsedBody()['_METHOD']) &&
            in_array($request->getParsedBody()['_METHOD'], ['PUT', 'DELETE'])) {
            $method = $request->getParsedBody()['_METHOD'];
            $request = $request->withMethod($method);
        }
        return $handler->handle($request);
    }
}
