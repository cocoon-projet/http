<?php
namespace Cocoon\Http\Facades;

use Cocoon\Http\HttpRequest;

class Request
{
    public static function __callStatic($name, $arguments)
    {
        $instance = new HttpRequest();
        return $instance->$name(...$arguments);
    }
}
