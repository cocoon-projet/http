<?php
declare(strict_types=1);

namespace Cocoon\Http;

use Cocoon\Http\Facades\Session;
use Cocoon\Http\Traits\ReturnInputAndErrorData;
use Laminas\Diactoros\Response\RedirectResponse as BaseRedirectResponse;
use Psr\Http\Message\UriInterface;

/**
 * Classe de réponse de redirection
 * 
 * Cette classe étend la réponse de redirection de base de Laminas Diactoros
 * en ajoutant des fonctionnalités pour les messages flash et la gestion des entrées.
 */
class RedirectResponse extends BaseRedirectResponse
{
    use ReturnInputAndErrorData;
    
    /**
     * URL de base de l'application
     * 
     * @var string|null
     */
    protected static ?string $baseUrl = null;

    /**
     * Constructeur
     * 
     * @param string|UriInterface $url URL de redirection
     * @param int $status Code d'état HTTP (301, 302, 303, 307, 308)
     * @param array $headers En-têtes HTTP
     * @throws \InvalidArgumentException Si l'URL est vide
     */
    public function __construct($url, int $status = 302, array $headers = [])
    {
        if (empty($url)) {
            throw new \InvalidArgumentException('Redirection impossible. L\'URL est vide.');
        }
        
        $uri = $this->normalizeUrl($url);
        
        parent::__construct($uri, $status, $headers);
    }
    
    /**
     * Définit l'URL de base de l'application
     * 
     * @param string $baseUrl URL de base
     * @return void
     */
    public static function setBaseUrl(string $baseUrl): void
    {
        self::$baseUrl = rtrim($baseUrl, '/');
    }
    
    /**
     * Obtient l'URL de base de l'application
     * 
     * @return string URL de base
     */
    public static function getBaseUrl(): string
    {
        if (self::$baseUrl === null) {
            $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
            self::$baseUrl = rtrim(dirname($scriptName), DIRECTORY_SEPARATOR);
        }
        
        return self::$baseUrl;
    }
    
    /**
     * Normalise une URL
     * 
     * @param string|UriInterface $url URL à normaliser
     * @return string|UriInterface URL normalisée
     */
    protected function normalizeUrl($url)
    {
        if ($url instanceof UriInterface) {
            return $url;
        }
        
        // Si l'URL est déjà absolue, la retourner telle quelle
        if (preg_match('/^https?:\/\//', $url)) {
            return $url;
        }
        
        // Si l'URL commence par un slash, l'ajouter à l'URL de base
        if (substr($url, 0, 1) === '/') {
            return self::getBaseUrl() . $url;
        }
        
        // Sinon, construire une URL relative à l'URL de base
        return self::getBaseUrl() . '/' . ltrim($url, '/');
    }

    /**
     * Ajoute un message flash
     * 
     * @param string $key Clé du message
     * @param string $message Contenu du message
     * @return $this
     */
    public function flash(string $key, string $message): self
    {
        Session::setFlash($key, $message);
        return $this;
    }
    
    /**
     * Ajoute un message flash de succès
     * 
     * @param string $message Message de succès
     * @return $this
     */
    public function withSuccess(string $message): self
    {
        return $this->flash('success', $message);
    }
    
    /**
     * Ajoute un message flash d'erreur
     * 
     * @param string $message Message d'erreur
     * @return $this
     */
    public function withError(string $message): self
    {
        return $this->flash('error', $message);
    }
    
    /**
     * Ajoute un message flash d'information
     * 
     * @param string $message Message d'information
     * @return $this
     */
    public function withInfo(string $message): self
    {
        return $this->flash('info', $message);
    }
    
    /**
     * Ajoute un message flash d'avertissement
     * 
     * @param string $message Message d'avertissement
     * @return $this
     */
    public function withWarning(string $message): self
    {
        return $this->flash('warning', $message);
    }
    
    /**
     * Ajoute plusieurs messages flash
     * 
     * @param array $messages Tableau associatif de messages (clé => message)
     * @return $this
     */
    public function withFlashes(array $messages): self
    {
        foreach ($messages as $key => $message) {
            Session::setFlash($key, $message);
        }
        
        return $this;
    }
    
    /**
     * Redirige avec les entrées actuelles
     * 
     * @return $this
     */
    public function withInput(): self
    {
        $this->withInput();
        return $this;
    }
    
    /**
     * Redirige avec les erreurs spécifiées
     * 
     * @param array $errors Tableau d'erreurs
     * @return $this
     */
    public function withErrors(array $errors): self
    {
        $this->withErrors($errors);
        return $this;
    }
    
    /**
     * Crée une redirection vers la page précédente
     * 
     * @param string $fallback URL de repli si la page précédente n'est pas disponible
     * @param int $status Code d'état HTTP
     * @param array $headers En-têtes HTTP
     * @return static
     */
    public static function back(string $fallback = '/', int $status = 302, array $headers = []): self
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? $fallback;
        return new static($referer, $status, $headers);
    }
    
    /**
     * Crée une redirection vers la page d'accueil
     * 
     * @param int $status Code d'état HTTP
     * @param array $headers En-têtes HTTP
     * @return static
     */
    public static function home(int $status = 302, array $headers = []): self
    {
        return new static('/', $status, $headers);
    }
    
    /**
     * Crée une redirection permanente (301)
     * 
     * @param string|UriInterface $url URL de redirection
     * @param array $headers En-têtes HTTP
     * @return static
     */
    public static function permanent($url, array $headers = []): self
    {
        return new static($url, 301, $headers);
    }
}
