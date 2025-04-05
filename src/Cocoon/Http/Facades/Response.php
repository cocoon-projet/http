<?php
declare(strict_types=1);

namespace Cocoon\Http\Facades;

use Cocoon\Http\HttpResponse;
use Psr\Http\Message\ResponseInterface;
use Laminas\Diactoros\Response\EmptyResponse;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\Response\TextResponse;
use Laminas\Diactoros\Response\XmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;

/**
 * Façade pour la classe HttpResponse
 */
class Response
{
    private static ?HttpResponse $instance = null;

    /**
     * Retourne l'instance unique de HttpResponse
     */
    public static function getInstance(): HttpResponse
    {
        if (self::$instance === null) {
            self::$instance = new HttpResponse();
        }
        return self::$instance;
    }

    /**
     * Redirige les appels de méthodes statiques vers l'instance
     */
    public static function __callStatic(string $method, array $arguments)
    {
        return self::getInstance()->$method(...$arguments);
    }

    public static function empty(int $status = 204, array $headers = []): ResponseInterface
    {
        return new EmptyResponse($status, $headers);
    }

    public static function html(string $html, int $status = 200, array $headers = []): ResponseInterface
    {
        return new HtmlResponse($html, $status, $headers);
    }

    public static function json($data, int $status = 200, array $headers = []): ResponseInterface
    {
        return new JsonResponse($data, $status, $headers);
    }

    public static function text(string $text, int $status = 200, array $headers = []): ResponseInterface
    {
        return new TextResponse($text, $status, $headers);
    }

    public static function xml(string $xml, int $status = 200, array $headers = []): ResponseInterface
    {
        return new XmlResponse($xml, $status, $headers);
    }

    public static function redirect(string $url, int $status = 302, array $headers = []): ResponseInterface
    {
        // Assurez-vous que l'URL commence par un slash si c'est un chemin relatif
        if (!preg_match('~^https?://~i', $url)) {
            $url = '/' . ltrim($url, '/');
        }
        return new RedirectResponse($url, $status, $headers);
    }
}
