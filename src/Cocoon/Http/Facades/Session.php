<?php
namespace Cocoon\Http\Facades;

use Cocoon\Http\HttpSession;

class Session
{
    public static function __callStatic($name, $arguments)
    {
        $instance = HttpSession::getInstance();
        return $instance->$name(...$arguments);
    }
}
