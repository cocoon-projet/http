<?php

namespace Cocoon\Http\Middleware;

use Cocoon\Http\Facades\Session;
use Cocoon\Http\Facades\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Class CsrfMiddleware
 * @package Cocoon\Http\Middleware
 */
class CsrfMiddleware implements MiddlewareInterface
{

    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     * @throws CsrfException
     * @internal param DelegateInterface $delegate
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler) :ResponseInterface
    {
        $methods = ['POST', 'PUT', 'DELETE'];
        if (in_array($request->getMethod(), $methods)) {
            if ($request->getParsedBody()['_token'] != null && Session::has('token')) {
                if (in_array($request->getParsedBody()['_token'], Session::get('token'))) {
                    Session::delete('token');
                    $response = $handler->handle($request);
                } else {
                    $response = Response::html('<h1>ERROR 500<h1>', '500');
                }
            } else {
                throw new \Exception('La protection " CSRF " n\'est pas présente');
            }
        } else {
            $response = $handler->handle($request);
        }

        return $response;
    }
}
