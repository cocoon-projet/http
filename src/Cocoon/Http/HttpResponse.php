<?php

namespace Cocoon\Http;

use Laminas\Diactoros\Response;
use Laminas\Diactoros\Response\XmlResponse;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\Response\TextResponse;
use Laminas\Diactoros\Response\EmptyResponse;

/**
 * Gestion des réponses
 *
 * Class ResponseFactory
 * @package Cocoon\Http
 */
class HttpResponse
{
    public function __construct()
    {
    }

    /**
     * Retourne une instance de Laminas\Diactoros\Response;
     *
     * @param string $content
     * @param int $status
     * @param array $headers
     * @return Response
     */
    public function response($content = 'php://memory', $status = 200, array $headers = [])
    {
        return new Response($content, $status, $headers);
    }

    /**
     * Retourne une instance de Laminas\Diactoros\HtmlResponse;
     *
     * @param string $content
     * @param int $status
     * @param array $headers
     * @return HtmlResponse
     */
    public function html($content = '', $status = 200, array $headers = [])
    {
        return new HtmlResponse($content, $status, $headers);
    }

    /**
     * Retourne une instance de Laminas\Diactoros\HtmlResponse;
     *
     * @param string $content
     * @param int $status
     * @param array $headers
     * @return HtmlResponse
     */
    public function xml($content = '', $status = 200, array $headers = [])
    {
        return new XmlResponse($content, $status, $headers);
    }

    /**
     * Retourne une instance de Laminas\Diactoros\TextResponse;
     *
     * @param string $content
     * @param int $status
     * @param array $headers
     * @return TextResponse
     */
    public function text($content = '', $status = 200, array $headers = [])
    {
        return new TextResponse($content, $status, $headers);
    }

    /**
     * Retourne une instance de Laminas\Diactoros\JsonResponse;
     *
     * @param string $content
     * @param int $status
     * @param array $headers
     * @return JsonResponse
     */
    public function json($content = '', $status = 200, array $headers = [])
    {
        return new JsonResponse($content, $status, $headers);
    }

    /**
     * Retourne une instance de Laminas\Diactoros\RedirectResponse;
     *
     * @param $uri
     * @param int $status
     * @param array $headers
     * @return \Cocoon\Http\RedirectResponse
     * @internal param $url
     */
    public function redirect($uri, $status = 302, array $headers = [])
    {
        return new RedirectResponse($uri, $status, $headers);
    }

    /**
     * Retourne une instance de Laminas\Diactoros\EmptyResponse;
     *
     * @return EmptyResponse
     */
    public function empty(int $status = 204, array $headers = [])
    {
        return new EmptyResponse($status, $headers);
    }
}
