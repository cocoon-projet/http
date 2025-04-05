<?php
declare(strict_types=1);

namespace Cocoon\Http\Facades;

use Cocoon\Http\HttpRequest;

/**
 * Façade pour la classe HttpRequest
 */
class Request
{
    /**
     * Retourne l'instance unique de HttpRequest
     */
    public static function getInstance(): HttpRequest
    {
        return HttpRequest::getInstance();
    }

    /**
     * Initialise la requête
     */
    public static function init(): \Psr\Http\Message\ServerRequestInterface
    {
        return HttpRequest::init();
    }

    /**
     * Redirige les appels de méthodes statiques vers l'instance
     */
    public static function __callStatic(string $method, array $arguments)
    {
        return self::getInstance()->$method(...$arguments);
    }
} 