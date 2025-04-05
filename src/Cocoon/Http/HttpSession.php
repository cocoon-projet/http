<?php
declare(strict_types=1);

namespace Cocoon\Http;

/**
 * Gestion des sessions
 *
 * Class HttpSession
 * @package Cocoon\Http
 */
class HttpSession
{
    /**
     * @var bool Indique si la session est démarrée
     */
    protected bool $isSession = false;
    
    /**
     * @var string|null Chemin de sauvegarde des sessions
     */
    protected static ?string $sessionPath = null;
    
    /**
     * @var bool Indique si l'ID de session a été régénéré
     */
    protected bool $isSessionRegenerate = false;
    
    /**
     * @var HttpSession|null Instance unique de la classe
     */
    private static ?HttpSession $instance = null;

    /**
     * @var array Configuration des cookies
     */
    private static array $cookieParams = [
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Lax'
    ];
    
    /**
     * Préfixes de clés de session
     */
    public const PREFIX_INPUT = 'input';
    public const PREFIX_FLASH = 'flash';
    public const TOKEN_KEY = 'token';
    public const EXPIRES_KEY = '_expires';
    public const META_KEY = '_meta';

    /**
     * Types de handlers de session
     */
    public const HANDLER_FILES = 'files';
    public const HANDLER_DATABASE = 'database';
    public const HANDLER_REDIS = 'redis';
    public const HANDLER_MEMCACHED = 'memcached';

    /**
     * Session constructor.
     * 
     * @param string|null $path Chemin de sauvegarde des sessions
     */
    private function __construct(?string $path = null)
    {
        if ($path !== null) {
            session_save_path($path);
        }
        
        if (session_id()) {
            $this->isSession = true;
        }
    }

    /**
     * Empêche le clonage de l'instance
     */
    private function __clone()
    {
    }

    /**
     * Définit le chemin de sauvegarde des sessions
     *
     * @param string $path Chemin de sauvegarde
     * @return void
     */
    public static function sessionPath(string $path): void
    {
        static::$sessionPath = $path;
    }

    /**
     * Instance unique de la classe HttpSession
     *
     * @return HttpSession
     */
    public static function getInstance(): HttpSession
    {
        if (is_null(self::$instance)) {
            self::$instance = new HttpSession(static::$sessionPath);
        }

        return self::$instance;
    }

    /**
     * Configure les paramètres des cookies de session
     *
     * @param array $params Paramètres des cookies
     * @return void
     */
    public static function setCookieParams(array $params): void
    {
        self::$cookieParams = array_merge(self::$cookieParams, $params);
    }

    /**
     * Configure le handler de session
     * 
     * @param string $type Type de handler (files, database, redis, memcached)
     * @param array $options Options de configuration
     * @return bool Succès de la configuration
     * @throws \Exception Si le type de handler n'est pas supporté
     */
    public function setHandler(string $type, array $options = []): bool
    {
        // Vérifier que la session n'est pas déjà démarrée
        if ($this->isSession) {
            throw new \Exception('Impossible de changer le handler après le démarrage de la session');
        }
        
        switch ($type) {
            case self::HANDLER_FILES:
                // Le handler par défaut, rien à faire
                return true;
                
            case self::HANDLER_DATABASE:
                if (!isset($options['pdo']) || !($options['pdo'] instanceof \PDO)) {
                    throw new \Exception('L\'option PDO est requise pour le handler de base de données');
                }
                
                $pdo = $options['pdo'];
                $tableName = $options['table'] ?? 'sessions';
                
                // Créer la table si elle n'existe pas
                if (isset($options['create_table']) && $options['create_table'] === true) {
                    $pdo->exec("CREATE TABLE IF NOT EXISTS `{$tableName}` (
                        `id` VARCHAR(128) NOT NULL PRIMARY KEY,
                        `data` TEXT NOT NULL,
                        `timestamp` INT UNSIGNED NOT NULL
                    )");
                }
                
                $handler = new \SessionHandler();
                session_set_save_handler($handler, true);
                return true;
                
            case self::HANDLER_REDIS:
                if (!extension_loaded('redis')) {
                    throw new \Exception('L\'extension Redis est requise pour le handler Redis');
                }
                
                $redis = new \Redis();
                $host = $options['host'] ?? '127.0.0.1';
                $port = $options['port'] ?? 6379;
                $timeout = $options['timeout'] ?? 0;
                $password = $options['password'] ?? null;
                
                if (!$redis->connect($host, $port, $timeout)) {
                    throw new \Exception('Impossible de se connecter au serveur Redis');
                }
                
                if ($password !== null) {
                    $redis->auth($password);
                }
                
                $prefix = $options['prefix'] ?? 'session:';
                $redis->setOption(\Redis::OPT_PREFIX, $prefix);
                
                ini_set('session.save_handler', 'redis');
                ini_set('session.save_path', "tcp://{$host}:{$port}");
                
                return true;
                
            case self::HANDLER_MEMCACHED:
                if (!extension_loaded('memcached')) {
                    throw new \Exception('L\'extension Memcached est requise pour le handler Memcached');
                }
                
                $memcached = new \Memcached();
                $host = $options['host'] ?? '127.0.0.1';
                $port = $options['port'] ?? 11211;
                
                $memcached->addServer($host, $port);
                
                ini_set('session.save_handler', 'memcached');
                ini_set('session.save_path', "{$host}:{$port}");
                
                return true;
                
            default:
                throw new \Exception("Type de handler non supporté: {$type}");
        }
    }

    /**
     * Démarre la session
     * 
     * @return void
     * @throws \RuntimeException Si la session ne peut pas être démarrée
     */
    public function start(): void
    {
        if ($this->isSession) {
            return;
        }

        // En environnement de test, on ignore la vérification des en-têtes
        if (!defined('PHPUNIT_COMPOSER_INSTALL') && headers_sent($file, $line)) {
            throw new \RuntimeException(
                sprintf(
                    'Les en-têtes ont déjà été envoyés dans le fichier "%s" à la ligne %d. ' .
                    'La session doit être démarrée avant tout envoi de contenu.',
                    $file,
                    $line
                )
            );
        }

        // Configuration des paramètres de cookie avant le démarrage
        if (!defined('PHPUNIT_COMPOSER_INSTALL')) {
            session_set_cookie_params(self::$cookieParams);
        }

        // Démarrage de la session avec gestion des erreurs
        if (session_status() === PHP_SESSION_NONE && !session_start()) {
            throw new \RuntimeException('Impossible de démarrer la session');
        }

        $this->isSession = true;
        
        // Nettoyer les données expirées
        $this->cleanExpiredData();
    }

    /**
     * Vérifie si une session peut être démarrée
     * 
     * @return bool
     */
    public function canStart(): bool
    {
        return !headers_sent() && !$this->isSession;
    }
    
    /**
     * Nettoie les données de session expirées
     * 
     * @return void
     */
    protected function cleanExpiredData(): void
    {
        if (!isset($_SESSION[self::EXPIRES_KEY]) || !is_array($_SESSION[self::EXPIRES_KEY])) {
            return;
        }
        
        $now = time();
        $expired = [];
        
        foreach ($_SESSION[self::EXPIRES_KEY] as $key => $expireTime) {
            if ($expireTime <= $now) {
                $expired[] = $key;
                unset($_SESSION[self::EXPIRES_KEY][$key]);
                unset($_SESSION[$key]);
            }
        }
        
        // Si toutes les expirations ont été traitées, supprimer la clé d'expiration
        if (empty($_SESSION[self::EXPIRES_KEY])) {
            unset($_SESSION[self::EXPIRES_KEY]);
        }
    }
    
    /**
     * Retourne si la session est ouverte
     *
     * @return boolean
     */
    public function isSession(): bool
    {
        return $this->isSession;
    }

    /**
     * Enregistre une valeur en session
     *
     * @param string $key Clé de la valeur
     * @param mixed $value Valeur à enregistrer
     * @param int|null $ttl Durée de vie en secondes (null = pas d'expiration)
     * @return void
     */
    public function set(string $key, $value, ?int $ttl = null): void
    {
        $this->start();
        $info = substr($key, 0, 5);
        if ($info === self::PREFIX_INPUT || $info === self::PREFIX_FLASH) {
            $_SESSION[$info][substr($key, 6)] = $value;
        } else {
            $_SESSION[$key] = $value;
            
            // Gérer l'expiration si un TTL est spécifié
            if ($ttl !== null) {
                if (!isset($_SESSION[self::EXPIRES_KEY])) {
                    $_SESSION[self::EXPIRES_KEY] = [];
                }
                $_SESSION[self::EXPIRES_KEY][$key] = time() + $ttl;
            }
        }
    }

    /**
     * Enregistre plusieurs valeurs en session
     * 
     * @param array $values Tableau associatif de valeurs
     * @param int|null $ttl Durée de vie en secondes (null = pas d'expiration)
     * @return void
     */
    public function setMultiple(array $values, ?int $ttl = null): void
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value, $ttl);
        }
    }

    /**
     * Enregistre un token CSRF
     *
     * @param string $token Token à enregistrer
     * @return void
     */
    private function setToken(string $token): void
    {
        $this->start();
        if (!isset($_SESSION[self::TOKEN_KEY])) {
            $_SESSION[self::TOKEN_KEY] = [];
        }
        $_SESSION[self::TOKEN_KEY][] = $token;
        
        // Limiter le nombre de tokens stockés (garder les 5 plus récents)
        if (count($_SESSION[self::TOKEN_KEY]) > 5) {
            array_shift($_SESSION[self::TOKEN_KEY]);
        }
    }

    /**
     * Vérifie si un token CSRF est valide
     * 
     * @param string $token Token à vérifier
     * @return bool
     */
    public function validateToken(string $token): bool
    {
        $this->start();
        if (!isset($_SESSION[self::TOKEN_KEY]) || !is_array($_SESSION[self::TOKEN_KEY])) {
            return false;
        }
        
        $valid = in_array($token, $_SESSION[self::TOKEN_KEY], true);
        
        // Supprimer le token après validation (usage unique)
        if ($valid) {
            $key = array_search($token, $_SESSION[self::TOKEN_KEY], true);
            unset($_SESSION[self::TOKEN_KEY][$key]);
            $_SESSION[self::TOKEN_KEY] = array_values($_SESSION[self::TOKEN_KEY]);
        }
        
        return $valid;
    }

    /**
     * Enregistre une valeur $_POST pour la retourner
     * dans un formulaire
     *
     * @param string $key Clé de la valeur
     * @param mixed $value Valeur à enregistrer
     * @return void
     */
    public function setInput(string $key, $value): void
    {
        $this->set(self::PREFIX_INPUT . '.' . $key, $value);
    }

    /**
     * Enregistre un message flash pour affichage
     * dans une vue
     *
     * @param string $key Clé du message
     * @param mixed $value Message à enregistrer
     * @return void
     */
    public function setFlash(string $key, $value): void
    {
        $this->start();
        
        if (!isset($_SESSION[self::PREFIX_FLASH])) {
            $_SESSION[self::PREFIX_FLASH] = [];
        }
        
        $_SESSION[self::PREFIX_FLASH][$key] = $value;
    }

    /**
     * Alias pour setFlash()
     *
     * @param string $key Clé du message
     * @param mixed $value Message à enregistrer
     * @return void
     */
    public function flash(string $key, $value): void
    {
        $this->setFlash($key, $value);
    }

    /**
     * Retourne un message flash (affichage)
     * Le message est supprimé après sa lecture
     *
     * @param string $key Clé du message
     * @return mixed
     */
    public function getFlash(string $key)
    {
        $this->start();
        $flashKey = self::PREFIX_FLASH . '.' . $key;
        
        if (!isset($_SESSION[self::PREFIX_FLASH]) || !isset($_SESSION[self::PREFIX_FLASH][$key])) {
            return null;
        }
        
        $value = $_SESSION[self::PREFIX_FLASH][$key];
        unset($_SESSION[self::PREFIX_FLASH][$key]);
        
        // Nettoyer le tableau flash s'il est vide
        if (empty($_SESSION[self::PREFIX_FLASH])) {
            unset($_SESSION[self::PREFIX_FLASH]);
        }
        
        return $value;
    }

    /**
     * Verifie si un message flash existe
     *
     * @param string $key Clé du message
     * @return bool
     */
    public function isFlash(string $key): bool
    {
        return $this->has(self::PREFIX_FLASH . '.' . $key);
    }

    /**
     * Supprime les messages flash
     * 
     * @return void
     */
    public function clearFlash(): void
    {
        $this->delete(self::PREFIX_FLASH);
    }

    /**
     * Verifie si une valeur de session existe
     *
     * @param string $key Clé à vérifier
     * @return bool
     */
    public function has(string $key): bool
    {
        $this->start();
        $info = substr($key, 0, 5);
        if ($info === self::PREFIX_INPUT || $info === self::PREFIX_FLASH) {
            $subKey = substr($key, 6);
            return isset($_SESSION[$info]) && isset($_SESSION[$info][$subKey]);
        }
        return isset($_SESSION[$key]);
    }

    /**
     * Vérifie si plusieurs clés existent en session
     * 
     * @param array $keys Tableau de clés à vérifier
     * @return bool True si toutes les clés existent
     */
    public function hasMultiple(array $keys): bool
    {
        foreach ($keys as $key) {
            if (!$this->has($key)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Retourne une valeur de session
     *
     * @param string $key Clé de la valeur
     * @param mixed $default Valeur par défaut si la clé n'existe pas
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        $this->start();
        
        if (!$this->has($key)) {
            return $default;
        }
        
        $info = substr($key, 0, 5);
        if ($info === self::PREFIX_INPUT || $info === self::PREFIX_FLASH) {
            return $_SESSION[$info][substr($key, 6)];
        }
        return $_SESSION[$key];
    }

    /**
     * Récupère plusieurs valeurs de session
     * 
     * @param array $keys Tableau de clés à récupérer
     * @param mixed $default Valeur par défaut pour les clés inexistantes
     * @return array Tableau associatif des valeurs
     */
    public function getMultiple(array $keys, $default = null): array
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->has($key) ? $this->get($key) : $default;
        }
        return $result;
    }

    /**
     * Retourne une valeur input de formulaire
     *
     * @param string $key Clé de la valeur
     * @param mixed $default Valeur par défaut
     * @return mixed
     */
    public function getInput(string $key, $default = null)
    {
        if ($this->has(self::PREFIX_INPUT . '.' . $key)) {
            return $this->get(self::PREFIX_INPUT . '.' . $key);
        } else {
            return $default;
        }
    }

    /**
     * Supprime les valeurs de session input
     * 
     * @return void
     */
    public function clearInput(): void
    {
        $this->delete(self::PREFIX_INPUT);
    }

    /**
     * Génère un token pour les formulaires
     * CSRF Protection
     *
     * @return string
     * @throws \Exception
     */
    public function token(): string
    {
        $token = bin2hex(random_bytes(32));
        $this->setToken($token);
        return $token;
    }

    /**
     * Supprime une valeur de session
     *
     * @param string|null $key Clé à supprimer
     * @return void
     */
    public function delete(?string $key = null): void
    {
        $this->start();
        if ($key === null) {
            $_SESSION = [];
            return;
        }
        
        if (isset($_SESSION[$key])) {
            unset($_SESSION[$key]);
            
            // Supprimer également l'expiration si elle existe
            if (isset($_SESSION[self::EXPIRES_KEY][$key])) {
                unset($_SESSION[self::EXPIRES_KEY][$key]);
                
                // Si toutes les expirations ont été traitées, supprimer la clé d'expiration
                if (empty($_SESSION[self::EXPIRES_KEY])) {
                    unset($_SESSION[self::EXPIRES_KEY]);
                }
            }
        }
    }

    /**
     * Supprime plusieurs valeurs de session
     * 
     * @param array $keys Tableau de clés à supprimer
     * @return void
     */
    public function deleteMultiple(array $keys): void
    {
        foreach ($keys as $key) {
            $this->delete($key);
        }
    }

    /**
     * Alias pour deleteMultiple()
     *
     * @param array $keys Tableau de clés à supprimer
     * @return void
     */
    public function remove(array $keys): void
    {
        $this->deleteMultiple($keys);
    }

    /**
     * Retourne l'ID de session
     *
     * @return string
     * @throws \Exception
     */
    public function getId(): string
    {
        if (!$this->isSession) {
            throw new \Exception('La session n\'est pas démarrée pour la lecture de l\'ID');
        }
        return session_id();
    }

    /**
     * Régénère un ID de session
     *
     * @param bool $destroy Détruire la session actuelle
     * @return void
     */
    public function regenerate(bool $destroy = false): void
    {
        $this->start();
        if ($this->isSessionRegenerate) {
            return;
        }
        session_regenerate_id($destroy);

        $this->isSessionRegenerate = true;
    }

    /**
     * Stocke des métadonnées de session
     * 
     * @param string $key Clé de la métadonnée
     * @param mixed $value Valeur de la métadonnée
     * @return void
     */
    public function setMeta(string $key, $value): void
    {
        $this->start();
        if (!isset($_SESSION[self::META_KEY])) {
            $_SESSION[self::META_KEY] = [];
        }
        $_SESSION[self::META_KEY][$key] = $value;
    }

    /**
     * Récupère une métadonnée de session
     * 
     * @param string $key Clé de la métadonnée
     * @param mixed $default Valeur par défaut
     * @return mixed
     */
    public function getMeta(string $key, $default = null)
    {
        $this->start();
        if (!isset($_SESSION[self::META_KEY]) || !isset($_SESSION[self::META_KEY][$key])) {
            return $default;
        }
        return $_SESSION[self::META_KEY][$key];
    }

    /**
     * Vérifie si une métadonnée existe
     * 
     * @param string $key Clé de la métadonnée
     * @return bool
     */
    public function hasMeta(string $key): bool
    {
        $this->start();
        return isset($_SESSION[self::META_KEY]) && isset($_SESSION[self::META_KEY][$key]);
    }

    /**
     * Supprime une métadonnée
     * 
     * @param string $key Clé de la métadonnée
     * @return void
     */
    public function deleteMeta(string $key): void
    {
        $this->start();
        if (isset($_SESSION[self::META_KEY]) && isset($_SESSION[self::META_KEY][$key])) {
            unset($_SESSION[self::META_KEY][$key]);
            
            // Si toutes les métadonnées ont été supprimées, supprimer la clé de métadonnées
            if (empty($_SESSION[self::META_KEY])) {
                unset($_SESSION[self::META_KEY]);
            }
        }
    }

    /**
     * Détruit la session
     * 
     * @return void
     */
    public function destroy(): void
    {
        $this->start();
        
        // Vider le tableau de session
        $_SESSION = [];
        
        // Détruire le cookie de session
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }
        
        // Détruire la session
        if (session_id()) {
            session_destroy();
        }
        
        $this->isSession = false;
        $this->isSessionRegenerate = false;
    }

    /**
     * Vide toutes les données de la session
     * 
     * @return void
     */
    public function clear(): void
    {
        $_SESSION = [];
    }

    /**
     * Retourne toutes les données de session
     *
     * @return array
     */
    public function all(): array
    {
        $this->start();
        $data = $_SESSION;
        
        // Exclure les clés système
        unset($data[self::TOKEN_KEY]);
        unset($data[self::EXPIRES_KEY]);
        unset($data[self::META_KEY]);
        unset($data[self::PREFIX_FLASH]);
        unset($data[self::PREFIX_INPUT]);
        
        return $data;
    }

    /**
     * Alias pour setMultiple()
     *
     * @param array $values Tableau associatif de valeurs
     * @return void
     */
    public function put(array $values): void
    {
        $this->setMultiple($values);
    }
}
