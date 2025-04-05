<?php
declare(strict_types=1);

namespace Cocoon\Http\Middleware;

use Cocoon\Http\Facades\Session;
use Cocoon\Http\Facades\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Middleware de protection CSRF
 * 
 * Ce middleware protège l'application contre les attaques CSRF (Cross-Site Request Forgery)
 * en vérifiant la présence et la validité d'un jeton pour toutes les requêtes non sécurisées.
 *
 * @package Cocoon\Http\Middleware
 */
class CsrfMiddleware implements MiddlewareInterface
{
    /**
     * Liste des méthodes HTTP qui nécessitent une protection CSRF
     */
    private const PROTECTED_METHODS = ['POST', 'PUT', 'DELETE', 'PATCH'];

    /**
     * Nom du champ contenant le jeton CSRF
     */
    private const TOKEN_FIELD = '_token';

    /**
     * Durée de vie du jeton en secondes (1 heure par défaut)
     */
    private const TOKEN_LIFETIME = 3600;

    /**
     * Liste des URLs exclues de la protection CSRF
     *
     * @var array
     */
    private array $excludedPaths = [];

    /**
     * Constructeur
     *
     * @param array $excludedPaths URLs à exclure de la protection CSRF
     */
    public function __construct(array $excludedPaths = [])
    {
        $this->excludedPaths = $excludedPaths;
    }

    /**
     * Traite la requête
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     * @throws \Exception Si la protection CSRF échoue
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Si la méthode n'est pas protégée ou si l'URL est exclue
        if (!$this->shouldProtect($request)) {
            return $handler->handle($request);
        }

        $token = $this->getTokenFromRequest($request);
        
        if (!$this->validateToken($token)) {
            // En production, on pourrait vouloir rediriger vers une page d'erreur
            // plutôt que de lancer une exception
            if (getenv('APP_ENV') === 'production') {
                return Response::forbidden('Invalid CSRF Token');
            }
            throw new \Exception('La protection CSRF a échoué : jeton invalide ou expiré');
        }

        // Nettoyer le jeton utilisé
        $this->removeUsedToken($token);

        // Générer un nouveau jeton pour la prochaine requête
        $this->refreshToken();

        return $handler->handle($request);
    }

    /**
     * Vérifie si la requête doit être protégée
     *
     * @param ServerRequestInterface $request
     * @return bool
     */
    private function shouldProtect(ServerRequestInterface $request): bool
    {
        // Vérifier si la méthode HTTP nécessite une protection
        if (!in_array($request->getMethod(), self::PROTECTED_METHODS, true)) {
            return false;
        }

        // Vérifier si l'URL est exclue
        $path = $request->getUri()->getPath();
        foreach ($this->excludedPaths as $excludedPath) {
            if (preg_match($excludedPath, $path)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Récupère le jeton CSRF de la requête
     *
     * @param ServerRequestInterface $request
     * @return string|null
     */
    private function getTokenFromRequest(ServerRequestInterface $request): ?string
    {
        // Vérifier d'abord dans le corps de la requête
        $parsedBody = $request->getParsedBody();
        if (isset($parsedBody[self::TOKEN_FIELD])) {
            return $parsedBody[self::TOKEN_FIELD];
        }

        // Vérifier ensuite dans les en-têtes (pour les requêtes AJAX)
        $headerToken = $request->getHeaderLine('X-CSRF-TOKEN');
        if (!empty($headerToken)) {
            return $headerToken;
        }

        return null;
    }

    /**
     * Valide un jeton CSRF
     *
     * @param string|null $token
     * @return bool
     */
    private function validateToken(?string $token): bool
    {
        if ($token === null || !Session::has('token')) {
            return false;
        }

        $tokens = Session::get('token');
        if (!is_array($tokens)) {
            return false;
        }

        foreach ($tokens as $storedToken) {
            if (hash_equals($storedToken['value'], $token)) {
                // Vérifier si le token n'a pas expiré
                if ($storedToken['expires'] > time()) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Supprime un jeton utilisé et les jetons expirés
     *
     * @param string $usedToken
     * @return void
     */
    private function removeUsedToken(string $usedToken): void
    {
        if (!Session::has('token')) {
            return;
        }

        $tokens = Session::get('token');
        $now = time();
        $newTokens = [];

        foreach ($tokens as $token) {
            // Garder les jetons non utilisés et non expirés
            if ($token['value'] !== $usedToken && $token['expires'] > $now) {
                $newTokens[] = $token;
            }
        }

        Session::set('token', $newTokens);
    }

    /**
     * Génère un nouveau jeton CSRF
     *
     * @return void
     */
    private function refreshToken(): void
    {
        $tokens = Session::get('token', []);
        
        // Limiter le nombre de jetons stockés (garder les 5 plus récents)
        if (count($tokens) >= 5) {
            array_shift($tokens);
        }

        $tokens[] = [
            'value' => bin2hex(random_bytes(32)),
            'expires' => time() + self::TOKEN_LIFETIME
        ];

        Session::set('token', $tokens);
    }
}
