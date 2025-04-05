<?php
declare(strict_types=1);

namespace Cocoon\Http\Facades;

use Cocoon\Http\HttpSession;

/**
 * Façade pour la classe HttpSession
 *
 * Cette façade permet d'accéder aux méthodes de HttpSession de manière statique.
 *
 * @method static void start()
 * @method static bool isSession()
 * @method static void set(string $key, mixed $value, int|null $ttl = null)
 * @method static void setMultiple(array $values, int|null $ttl = null)
 * @method static mixed get(string $key)
 * @method static array getMultiple(array $keys, mixed $default = null)
 * @method static bool has(string $key)
 * @method static bool hasMultiple(array $keys)
 * @method static void delete(string|null $key = null)
 * @method static void deleteMultiple(array $keys)
 * @method static string getId()
 * @method static void regenerate(bool $destroy = false)
 * @method static void destroy()
 * @method static string token()
 * @method static bool validateToken(string $token)
 * @method static void setFlash(string $key, string $value)
 * @method static string getFlash(string $key)
 * @method static bool isFlash(string $key)
 * @method static void clearFlash()
 * @method static void setInput(string $key, mixed $value)
 * @method static mixed getInput(string $key, mixed $default = null)
 * @method static void clearInput()
 * @method static void setMeta(string $key, mixed $value)
 * @method static mixed getMeta(string $key, mixed $default = null)
 * @method static bool hasMeta(string $key)
 * @method static void deleteMeta(string $key)
 * @method static bool setHandler(string $type, array $options = [])
 */
class Session
{
    /**
     * Gère les appels statiques aux méthodes de HttpSession
     *
     * @param string $name Nom de la méthode
     * @param array $arguments Arguments de la méthode
     * @return mixed Résultat de l'appel de méthode
     * @throws \BadMethodCallException Si la méthode n'existe pas
     */
    public static function __callStatic(string $name, array $arguments)
    {
        $instance = HttpSession::getInstance();
        
        if (!method_exists($instance, $name)) {
            throw new \BadMethodCallException(
                sprintf('La méthode %s::%s n\'existe pas', HttpSession::class, $name)
            );
        }
        
        return $instance->$name(...$arguments);
    }
    
    /**
     * Définit le chemin de sauvegarde des sessions
     *
     * Méthode d'aide qui appelle directement la méthode statique de HttpSession
     *
     * @param string $path Chemin de sauvegarde
     * @return void
     */
    public static function sessionPath(string $path): void
    {
        HttpSession::sessionPath($path);
    }
    
    /**
     * Obtient l'instance de HttpSession
     *
     * @return HttpSession
     */
    public static function getInstance(): HttpSession
    {
        return HttpSession::getInstance();
    }
    
    /**
     * Démarre la session et retourne l'instance
     *
     * Méthode fluide qui permet d'enchaîner les appels
     *
     * @return HttpSession
     */
    public static function startAndGetInstance(): HttpSession
    {
        $instance = HttpSession::getInstance();
        $instance->start();
        return $instance;
    }
    
    /**
     * Vérifie si une valeur existe en session et la retourne, sinon retourne la valeur par défaut
     *
     * @param string $key Clé à vérifier
     * @param mixed $default Valeur par défaut
     * @return mixed
     */
    public static function getOr(string $key, $default = null)
    {
        $instance = HttpSession::getInstance();
        return $instance->has($key) ? $instance->get($key) : $default;
    }
    
    /**
     * Définit une valeur en session uniquement si la clé n'existe pas déjà
     *
     * @param string $key Clé à définir
     * @param mixed $value Valeur à enregistrer
     * @param int|null $ttl Durée de vie en secondes
     * @return bool True si la valeur a été définie, false si la clé existait déjà
     */
    public static function setIfNotExists(string $key, $value, ?int $ttl = null): bool
    {
        $instance = HttpSession::getInstance();
        if (!$instance->has($key)) {
            $instance->set($key, $value, $ttl);
            return true;
        }
        return false;
    }
    
    /**
     * Récupère une valeur de session puis la supprime
     *
     * @param string $key Clé à récupérer et supprimer
     * @param mixed $default Valeur par défaut si la clé n'existe pas
     * @return mixed
     */
    public static function pull(string $key, $default = null)
    {
        $instance = HttpSession::getInstance();
        $value = $instance->has($key) ? $instance->get($key) : $default;
        
        if ($instance->has($key)) {
            $instance->delete($key);
        }
        
        return $value;
    }
    
    /**
     * Incrémente une valeur numérique en session
     *
     * @param string $key Clé à incrémenter
     * @param int $amount Montant de l'incrémentation
     * @return int Nouvelle valeur
     */
    public static function increment(string $key, int $amount = 1): int
    {
        $instance = HttpSession::getInstance();
        $value = (int)($instance->has($key) ? $instance->get($key) : 0);
        $value += $amount;
        $instance->set($key, $value);
        return $value;
    }
    
    /**
     * Décrémente une valeur numérique en session
     *
     * @param string $key Clé à décrémenter
     * @param int $amount Montant de la décrémentation
     * @return int Nouvelle valeur
     */
    public static function decrement(string $key, int $amount = 1): int
    {
        return self::increment($key, -$amount);
    }
}
