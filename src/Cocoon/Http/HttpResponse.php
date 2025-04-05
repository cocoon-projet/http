<?php
declare(strict_types=1);

namespace Cocoon\Http;

use Laminas\Diactoros\Response;
use Laminas\Diactoros\Response\XmlResponse;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\Response\TextResponse;
use Laminas\Diactoros\Response\EmptyResponse;
use Laminas\Diactoros\Stream;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Gestion des réponses HTTP
 *
 * Cette classe fournit des méthodes pour créer différents types de réponses HTTP
 * conformes à la norme PSR-7.
 *
 * @package Cocoon\Http
 */
class HttpResponse
{
    /**
     * En-têtes par défaut pour les réponses
     *
     * @var array
     */
    protected array $defaultHeaders = [];

    /**
     * Code d'état HTTP par défaut
     *
     * @var int
     */
    protected int $defaultStatus = 200;

    /**
     * Constructeur
     */
    public function __construct()
    {
    }

    /**
     * Définit les en-têtes par défaut pour toutes les réponses
     *
     * @param array $headers En-têtes à définir
     * @return $this
     */
    public function withHeaders(array $headers): self
    {
        $this->defaultHeaders = array_merge($this->defaultHeaders, $headers);
        return $this;
    }

    /**
     * Définit le code d'état par défaut pour toutes les réponses
     *
     * @param int $status Code d'état HTTP
     * @return $this
     */
    public function withStatus(int $status): self
    {
        $this->defaultStatus = $status;
        return $this;
    }

    /**
     * Retourne une instance de Laminas\Diactoros\Response
     *
     * @param string|resource|StreamInterface $content Contenu de la réponse
     * @param int|null $status Code d'état HTTP
     * @param array $headers En-têtes HTTP
     * @return Response
     */
    public function response($content = 'php://memory', ?int $status = null, array $headers = []): Response
    {
        $status = $status ?? $this->defaultStatus;
        $headers = array_merge($this->defaultHeaders, $headers);

        if (is_string($content) && $content !== 'php://memory') {
            $stream = new Stream('php://temp', 'wb+');
            $stream->write($content);
            $stream->rewind();
            return new Response($stream, $status, $headers);
        }

        return new Response($content, $status, $headers);
    }

    /**
     * Retourne une instance de Laminas\Diactoros\HtmlResponse
     *
     * @param string $content Contenu HTML
     * @param int|null $status Code d'état HTTP
     * @param array $headers En-têtes HTTP
     * @return HtmlResponse
     */
    public function html(string $content = '', ?int $status = null, array $headers = []): HtmlResponse
    {
        $status = $status ?? $this->defaultStatus;
        $headers = array_merge($this->defaultHeaders, $headers);
        return new HtmlResponse($content, $status, $headers);
    }

    /**
     * Retourne une instance de Laminas\Diactoros\XmlResponse
     *
     * @param string $content Contenu XML
     * @param int|null $status Code d'état HTTP
     * @param array $headers En-têtes HTTP
     * @return XmlResponse
     */
    public function xml(string $content = '', ?int $status = null, array $headers = []): XmlResponse
    {
        $status = $status ?? $this->defaultStatus;
        $headers = array_merge($this->defaultHeaders, $headers);
        return new XmlResponse($content, $status, $headers);
    }

    /**
     * Retourne une instance de Laminas\Diactoros\TextResponse
     *
     * @param string $content Contenu texte
     * @param int|null $status Code d'état HTTP
     * @param array $headers En-têtes HTTP
     * @return TextResponse
     */
    public function text(string $content = '', ?int $status = null, array $headers = []): TextResponse
    {
        $status = $status ?? $this->defaultStatus;
        $headers = array_merge($this->defaultHeaders, $headers);
        return new TextResponse($content, $status, $headers);
    }

    /**
     * Retourne une instance de Laminas\Diactoros\JsonResponse
     *
     * @param mixed $data Données à encoder en JSON
     * @param int|null $status Code d'état HTTP
     * @param array $headers En-têtes HTTP
     * @param int $encodingOptions Options d'encodage JSON
     * @return JsonResponse
     */
    public function json($data = '', ?int $status = null, array $headers = [], int $encodingOptions = 0): JsonResponse
    {
        $status = $status ?? $this->defaultStatus;
        $headers = array_merge($this->defaultHeaders, $headers);
        return new JsonResponse($data, $status, $headers, $encodingOptions);
    }

    /**
     * Retourne une instance de RedirectResponse
     *
     * @param string $uri URI de redirection
     * @param int $status Code d'état HTTP (301, 302, 303, 307, 308)
     * @param array $headers En-têtes HTTP
     * @return RedirectResponse
     */
    public function redirect(string $uri, int $status = 302, array $headers = []): RedirectResponse
    {
        $headers = array_merge($this->defaultHeaders, $headers);
        return new RedirectResponse($uri, $status, $headers);
    }

    /**
     * Retourne une instance de Laminas\Diactoros\EmptyResponse
     *
     * @param int|null $status Code d'état HTTP
     * @param array $headers En-têtes HTTP
     * @return EmptyResponse
     */
    public function empty(?int $status = 204, array $headers = []): EmptyResponse
    {
        $status = $status ?? 204;
        $headers = array_merge($this->defaultHeaders, $headers);
        return new EmptyResponse($status, $headers);
    }

    /**
     * Retourne une réponse pour un fichier
     *
     * @param string $path Chemin du fichier
     * @param string|null $contentType Type MIME du fichier
     * @param int|null $status Code d'état HTTP
     * @param array $headers En-têtes HTTP
     * @return ResponseInterface
     * @throws \RuntimeException Si le fichier n'existe pas ou n'est pas lisible
     */
    public function file(
        string $path,
        ?string $contentType = null,
        ?int $status = null,
        array $headers = []
    ): ResponseInterface {
        if (!file_exists($path) || !is_readable($path)) {
            throw new \RuntimeException("Le fichier '$path' n'existe pas ou n'est pas lisible");
        }

        $status = $status ?? $this->defaultStatus;
        $headers = array_merge($this->defaultHeaders, $headers);

        if ($contentType === null) {
            $contentType = $this->getMimeType($path);
        }

        $headers['Content-Type'] = $contentType;
        $headers['Content-Length'] = filesize($path);

        $stream = new Stream($path, 'r');
        return new Response($stream, $status, $headers);
    }

    /**
     * Retourne une réponse pour télécharger un fichier
     *
     * @param string $path Chemin du fichier
     * @param string|null $filename Nom du fichier pour le téléchargement
     * @param int|null $status Code d'état HTTP
     * @param array $headers En-têtes HTTP
     * @return ResponseInterface
     * @throws \RuntimeException Si le fichier n'existe pas ou n'est pas lisible
     */
    public function download(
        string $path,
        ?string $filename = null,
        ?int $status = null,
        array $headers = []
    ): ResponseInterface {
        if (!file_exists($path) || !is_readable($path)) {
            throw new \RuntimeException("Le fichier '$path' n'existe pas ou n'est pas lisible");
        }

        $status = $status ?? $this->defaultStatus;
        $headers = array_merge($this->defaultHeaders, $headers);

        $filename = $filename ?? basename($path);
        $contentType = $this->getMimeType($path);

        $headers['Content-Type'] = $contentType;
        $headers['Content-Length'] = filesize($path);
        $headers['Content-Disposition'] = 'attachment; filename="' . $filename . '"';

        $stream = new Stream($path, 'r');
        return new Response($stream, $status, $headers);
    }

    /**
     * Détermine le type MIME d'un fichier
     *
     * @param string $path Chemin du fichier
     * @return string Type MIME
     */
    protected function getMimeType(string $path): string
    {
        $extension = pathinfo($path, PATHINFO_EXTENSION);

        $mimeTypes = [
            'txt' => 'text/plain',
            'html' => 'text/html',
            'css' => 'text/css',
            'js' => 'application/javascript',
            'json' => 'application/json',
            'xml' => 'application/xml',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'zip' => 'application/zip',
            'mp3' => 'audio/mpeg',
            'mp4' => 'video/mp4',
        ];

        return $mimeTypes[strtolower($extension)] ?? 'application/octet-stream';
    }

    /**
     * Retourne une réponse 404 Not Found
     *
     * @param string $message Message d'erreur
     * @param array $headers En-têtes HTTP
     * @return ResponseInterface
     */
    public function notFound(string $message = 'Not Found', array $headers = []): ResponseInterface
    {
        $headers = array_merge($this->defaultHeaders, $headers);
        return $this->html('<h1>404 Not Found</h1><p>' . htmlspecialchars($message) . '</p>', 404, $headers);
    }

    /**
     * Retourne une réponse 403 Forbidden
     *
     * @param string $message Message d'erreur
     * @param array $headers En-têtes HTTP
     * @return ResponseInterface
     */
    public function forbidden(string $message = 'Forbidden', array $headers = []): ResponseInterface
    {
        $headers = array_merge($this->defaultHeaders, $headers);
        return $this->html('<h1>403 Forbidden</h1><p>' . htmlspecialchars($message) . '</p>', 403, $headers);
    }

    /**
     * Retourne une réponse 401 Unauthorized
     *
     * @param string $message Message d'erreur
     * @param array $headers En-têtes HTTP
     * @return ResponseInterface
     */
    public function unauthorized(string $message = 'Unauthorized', array $headers = []): ResponseInterface
    {
        $headers = array_merge($this->defaultHeaders, $headers);
        return $this->html('<h1>401 Unauthorized</h1><p>' . htmlspecialchars($message) . '</p>', 401, $headers);
    }

    /**
     * Retourne une réponse 400 Bad Request
     *
     * @param string $message Message d'erreur
     * @param array $headers En-têtes HTTP
     * @return ResponseInterface
     */
    public function badRequest(string $message = 'Bad Request', array $headers = []): ResponseInterface
    {
        $headers = array_merge($this->defaultHeaders, $headers);
        return $this->html('<h1>400 Bad Request</h1><p>' . htmlspecialchars($message) . '</p>', 400, $headers);
    }

    /**
     * Retourne une réponse 500 Internal Server Error
     *
     * @param string $message Message d'erreur
     * @param array $headers En-têtes HTTP
     * @return ResponseInterface
     */
    public function serverError(
        string $message = 'Internal Server Error',
        array $headers = []
    ): ResponseInterface {
        $headers = array_merge($this->defaultHeaders, $headers);
        return $this->html(
            '<h1>500 Internal Server Error</h1><p>' .
            htmlspecialchars($message)
            . '</p>',
            500,
            $headers
        );
    }
}
