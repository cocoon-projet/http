<?php
declare(strict_types=1);

namespace Cocoon\Http\Middleware;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Middleware pour la gestion des méthodes HTTP
 * 
 * Ce middleware permet de gérer les méthodes HTTP alternatives via le champ _METHOD
 * pour les navigateurs qui ne supportent que GET et POST.
 *
 * @package Cocoon\Http\Middleware
 */
class HttpMethodMiddleware implements MiddlewareInterface
{
    /**
     * Liste des méthodes HTTP valides
     */
    private const VALID_METHODS = [
        'GET',
        'POST',
        'PUT',
        'PATCH',
        'DELETE',
        'HEAD',
        'OPTIONS',
        'TRACE',
        'CONNECT'
    ];

    /**
     * Nom du champ pour la méthode HTTP alternative
     */
    private const METHOD_OVERRIDE_PARAM = '_METHOD';

    /**
     * En-tête HTTP pour la méthode alternative
     */
    private const METHOD_OVERRIDE_HEADER = 'X-HTTP-Method-Override';

    /**
     * Traite la requête
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $method = $this->getOverrideMethod($request);

        if ($method !== null) {
            $request = $request->withMethod($method);
        }

        return $handler->handle($request);
    }

    /**
     * Récupère la méthode HTTP alternative depuis la requête
     *
     * @param ServerRequestInterface $request
     * @return string|null
     */
    private function getOverrideMethod(ServerRequestInterface $request): ?string
    {
        // Vérifier d'abord l'en-tête HTTP
        $headerMethod = $request->getHeaderLine(self::METHOD_OVERRIDE_HEADER);
        if ($headerMethod && $this->isValidMethod($headerMethod)) {
            return strtoupper($headerMethod);
        }

        // Ensuite vérifier le corps de la requête
        $parsedBody = $request->getParsedBody();
        if (is_array($parsedBody) && isset($parsedBody[self::METHOD_OVERRIDE_PARAM])) {
            $bodyMethod = $parsedBody[self::METHOD_OVERRIDE_PARAM];
            if ($this->isValidMethod($bodyMethod)) {
                return strtoupper($bodyMethod);
            }
        }

        return null;
    }

    /**
     * Vérifie si la méthode HTTP est valide
     *
     * @param string $method
     * @return bool
     */
    private function isValidMethod(string $method): bool
    {
        return in_array(strtoupper($method), self::VALID_METHODS, true);
    }

    /**
     * Vérifie si la méthode est sécurisée (ne modifie pas les ressources)
     *
     * @param string $method
     * @return bool
     */
    private function isSafeMethod(string $method): bool
    {
        return in_array(strtoupper($method), ['GET', 'HEAD', 'OPTIONS', 'TRACE'], true);
    }

    /**
     * Vérifie si la méthode est idempotente
     *
     * @param string $method
     * @return bool
     */
    private function isIdempotentMethod(string $method): bool
    {
        return in_array(strtoupper($method), ['GET', 'HEAD', 'PUT', 'DELETE', 'OPTIONS', 'TRACE'], true);
    }
}
