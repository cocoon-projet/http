<?php
declare(strict_types=1);

namespace Cocoon\Http;

use Cocoon\Control\Validator;
use Cocoon\Http\Facades\Response;
use Cocoon\Http\Traits\ReturnInputAndErrorData;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;
use Laminas\Diactoros\ServerRequestFactory;

/**
 * Classe de gestion des requêtes HTTP
 *
 * Cette classe encapsule les fonctionnalités de traitement des requêtes HTTP
 * en fournissant des méthodes simples pour accéder aux données de la requête.
 */
class HttpRequest
{
    /**
     * Trait pour la gestion des entrées et des erreurs
     */
    use ReturnInputAndErrorData;

    /**
     * Instance unique de la classe
     */
    private static ?self $instance = null;

    /**
     * Instance de la requête PSR-7
     */
    private ServerRequestInterface $request;

    /**
     * Constructeur privé pour le pattern Singleton
     */
    private function __construct()
    {
        $this->request = ServerRequestFactory::fromGlobals();
    }

    /**
     * Retourne l'instance unique
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Initialise et retourne une nouvelle requête
     */
    public static function init(): ServerRequestInterface
    {
        return self::getInstance()->request;
    }

    /**
     * Retourne les paramètres du serveur
     */
    public function getServerParams(): array
    {
        return $_SERVER;
    }

    /**
     * Retourne un paramètre de la query string
     */
    public function query(?string $key = null)
    {
        if ($key === null) {
            return $_GET;
        }
        return $_GET[$key] ?? null;
    }

    /**
     * Retourne un paramètre du corps de la requête
     */
    public function input(string $key)
    {
        return $_POST[$key] ?? null;
    }

    /**
     * Retourne uniquement les paramètres spécifiés
     */
    public function only(array $keys): array
    {
        return array_intersect_key($_POST, array_flip($keys));
    }

    /**
     * Retourne tous les paramètres du serveur
     */
    public function all(): array
    {
        return $_SERVER;
    }

    /**
     * Retourne la méthode de la requête
     */
    public function getMethod(): string
    {
        return $this->request->getMethod();
    }

    /**
     * Retourne le corps parsé de la requête
     */
    public function getParsedBody()
    {
        return $this->request->getParsedBody();
    }

    /**
     * Récupère un fichier téléchargé
     *
     * @param string|null $key Clé du fichier à récupérer, null pour tous les fichiers
     * @return UploadedFileInterface|array|null
     */
    public function file(?string $key = null)
    {
        $files = $this->request->getUploadedFiles();
        
        if ($key === null) {
            return $files;
        }
        
        return $files[$key] ?? null;
    }

    /**
     * Vérifie si un fichier a été téléchargé
     *
     * @param string $key Clé du fichier à vérifier
     * @return bool
     */
    public function hasFile(string $key): bool
    {
        $files = $this->request->getUploadedFiles();
        return isset($files[$key]) && $files[$key]->getSize() > 0;
    }

    /**
     * Vérifie si une donnée existe dans la requête
     *
     * @param string $key Clé à vérifier
     * @return bool
     */
    public function has(string $key): bool
    {
        $body = $this->request->getParsedBody() ?? [];
        return isset($body[$key]);
    }

    /**
     * Récupère toutes les données sauf celles spécifiées
     *
     * @param array $keys Tableau des clés à exclure
     * @return array
     */
    public function except(array $keys = []): array
    {
        $request = $this->request->getParsedBody() ?? [];
        
        foreach ($keys as $key) {
            unset($request[$key]);
        }
        
        return $request;
    }

    /**
     * Valide les données de la requête
     *
     * @param array $rules Règles de validation
     * @param array|null $messages Messages d'erreur personnalisés
     * @return bool True si la validation réussit
     */
    public function validate(array $rules = [], ?array $messages = null): bool
    {
        $valid = new Validator();
        $valid->validate($rules, $messages);
        
        if ($valid->fails()) {
            $errors = $valid->errors()->all();
            $this->withErrors($errors);
            $this->withInput();
            
            $referer = $this->request->getServerParams()['HTTP_REFERER'] ?? '';
            $response = Response::redirect($referer);
            HttpResponseSend::emit($response, true);
            
            return false;
        }
        
        return true;
    }

    /**
     * Vérifie si la méthode HTTP correspond à celle spécifiée
     *
     * @param string $method Méthode à vérifier
     * @return bool
     */
    public function isMethod(string $method): bool
    {
        return $this->getMethod() === strtoupper($method);
    }

    /**
     * Vérifie si la requête est une requête GET
     *
     * @return bool
     */
    public function isGet(): bool
    {
        return $this->isMethod('GET');
    }

    /**
     * Vérifie si la requête est une requête POST
     *
     * @return bool
     */
    public function isPost(): bool
    {
        return $this->isMethod('POST');
    }

    /**
     * Vérifie si la requête est une requête PUT
     *
     * @return bool
     */
    public function isPut(): bool
    {
        return $this->isMethod('PUT');
    }

    /**
     * Vérifie si la requête est une requête DELETE
     *
     * @return bool
     */
    public function isDelete(): bool
    {
        return $this->isMethod('DELETE');
    }

    /**
     * Vérifie si la requête est une requête PATCH
     *
     * @return bool
     */
    public function isPatch(): bool
    {
        return $this->isMethod('PATCH');
    }

    /**
     * Vérifie si la requête est une requête AJAX
     *
     * @return bool
     */
    public function isAjax(): bool
    {
        $headers = $this->request->getHeaders();
        return isset($headers['X-Requested-With']) &&
               $headers['X-Requested-With'][0] === 'XMLHttpRequest';
    }

    /**
     * Vérifie si la requête est sécurisée (HTTPS)
     *
     * @return bool
     */
    public function isSecure(): bool
    {
        $server = $this->request->getServerParams();
        return isset($server['HTTPS']) && $server['HTTPS'] !== 'off';
    }

    /**
     * Récupère l'adresse IP du client
     *
     * @return string
     */
    public function ip(): string
    {
        $server = $this->request->getServerParams();
        
        $keys = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];
        
        foreach ($keys as $key) {
            if (isset($server[$key])) {
                $ips = explode(',', $server[$key]);
                $ip = trim($ips[0]);
                
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        
        return '0.0.0.0';
    }

    /**
     * Récupère l'agent utilisateur
     *
     * @return string|null
     */
    public function userAgent(): ?string
    {
        $headers = $this->request->getHeaders();
        return $headers['User-Agent'][0] ?? null;
    }

    /**
     * Récupère l'URL complète de la requête
     *
     * @return string
     */
    public function url(): string
    {
        $uri = $this->request->getUri();
        return (string) $uri;
    }

    /**
     * Récupère le chemin de la requête
     *
     * @return string
     */
    public function path(): string
    {
        return $this->request->getUri()->getPath();
    }

    /**
     * Récupère un en-tête de la requête
     *
     * @param string $key Nom de l'en-tête
     * @param string|null $default Valeur par défaut
     * @return string|null
     */
    public function header(string $key, ?string $default = null): ?string
    {
        $headers = $this->request->getHeaders();
        $normalizedKey = str_replace('-', ' ', $key);
        $normalizedKey = ucwords($normalizedKey);
        $normalizedKey = str_replace(' ', '-', $normalizedKey);
        
        return $headers[$normalizedKey][0] ?? $default;
    }

    /**
     * Récupère tous les en-têtes de la requête
     *
     * @return array
     */
    public function headers(): array
    {
        $headers = $this->request->getHeaders();
        $result = [];
        
        foreach ($headers as $key => $values) {
            $result[$key] = $values[0];
        }
        
        return $result;
    }

    /**
     * Récupère un cookie
     *
     * @param string $key Nom du cookie
     * @param mixed $default Valeur par défaut
     * @return mixed
     */
    public function cookie(string $key, $default = null)
    {
        $cookies = $this->request->getCookieParams();
        return $cookies[$key] ?? $default;
    }

    /**
     * Récupère tous les cookies
     *
     * @return array
     */
    public function cookies(): array
    {
        return $this->request->getCookieParams();
    }

    /**
     * Retourne une instance de ServerRequest pour utiliser d'autres méthodes
     *
     * @return ServerRequestInterface
     */
    public function get(): ServerRequestInterface
    {
        return $this->request;
    }
}
