<?php

namespace Cocoon\Http;

use Laminas\Diactoros\ServerRequestFactory;
use Laminas\Diactoros\ServerRequestFactory as BaseServerRequestFactory;

/**
 * Gestion HTTP Request
 *
 * Class ServerRequest
 * @package Cocoon\Http
 */
class ServerRequest extends BaseServerRequestFactory
{
    public static function init()
    {
        return  ServerRequestFactory::fromGlobals(
            $_SERVER,
            $_GET,
            $_POST,
            $_COOKIE,
            $_FILES
        );
    }
}
