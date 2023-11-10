<?php
namespace Cocoon\Http\Facades;

use Cocoon\Http\HttpResponse;

class Response
{
	public static function __callStatic($name, $arguments)
    {
        $instance = new HttpResponse();
        return $instance->$name(...$arguments);
    }
}