<?php

namespace Cocoon\Http;

/**
 * Gestion des sessions
 *
 * Class Session
 * @package Cocoon\Http
 */
class HttpSession
{

    protected $isSession;
    protected static $sessionPath = null;
    protected $isSessionRegenerate;
    private static $instance = null;

    /**
     * Session constructor.
     */
    private function __construct($path = null)
    {
        if ($path != null) {
            session_save_path($path);
        }
        if (session_id()) {
            $this->isSession = true;
        }
    }

    private function __clone()
    {
    }

    public static function sessionPath($path)
    {
        static::$sessionPath = $path;
    }

    /**
     * Instance unique de la classe Session
     *
     * @return Session|null
     */
    public static function getInstance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new HttpSession(static::$sessionPath);
        }

        return self::$instance;
    }

    /**
     * Démarre la session
     */
    public function start()
    {
        if ($this->isSession) {
            return;
        }

        if (!session_id()) {
            session_start();
        }

        $this->isSession = true;
    }

    /**
     * Enregistre une valeur en session
     *
     * @param string $key
     * @param string $value
     */
    public function set($key, $value)
    {
        $this->start();
        $info = substr($key, 0, 5);
        if ($info == 'input' or $info == 'flash') {
            $_SESSION[$info][substr($key, 6)] = $value;
        } else {
            $_SESSION[$key] = $value;
        }
    }

    private function setToken($token)
    {
        $this->start();
        $_SESSION['token'][] = $token;
    }

    /**
     * Enregistre une valeur $_POST pour la retourner
     * dans un formulaire
     *
     * @param string $key
     * @param string $value
     */
    public function setInput($key, $value)
    {
        $this->set('input.' . $key, $value);
    }

    /**
     * Enregistre un message flash pour affichage
     * dans une vue
     *
     * @param string $key
     * @param string $value
     */
    public function setFlash($key, $value)
    {
        $this->set('flash.' . $key, $value);
    }

    /**
     * Retourne un message flash (affichage)
     *
     * @param string $key
     * @return mixed
     */
    public function getFlash($key) :string
    {
        return $this->get('flash.' . $key);
    }

    /**
     * Verifie si un message flash existe
     *
     * @param string $key
     * @return bool
     */
    public function isFlash($key) :bool
    {
        return $this->has('flash.' . $key);
    }

    /**
     * Supprime les messages flass
     */
    public function clearFlash()
    {
        $this->delete('flash');
    }

    /**
     * Verifie si une valeur de session existe
     *
     * @param string $key
     * @return bool
     */
    public function has($key) :bool
    {
        $this->start();
        $info = substr($key, 0, 5);
        if ($info == 'input' or $info == 'flash') {
            return isset($_SESSION[$info][substr($key, 6)]);
        }
        return isset($_SESSION[$key]);
    }

    /**
     * Retourne une valeur de session
     *
     * @param string $key
     * @return mixed
     */
    public function get($key)
    {
        $this->start();
        $info = substr($key, 0, 5);
        if ($info == 'input' or $info == 'flash') {
            return $_SESSION[$info][substr($key, 6)];
        }
        return $_SESSION[$key];
    }

    /**
     * Retourne une valeur input de formulaire
     *
     * @param string $key
     * @param null $default
     * @return string|null
     */
    public function getInput($key, $default = null)
    {
        if ($this->has('input.' . $key)) {
            return $this->get('input.' . $key);
        } else {
            return $default;
        }
    }

    /**
     * Supprime les valeurs de session input
     */
    public function clearInput()
    {
        $this->delete('input');
    }

    /**
     * Génère un token pour les formulaires
     * CSRF Protection
     *
     * @return string
     * @throws \Exception
     */
    public function token()
    {
        $token = bin2hex(random_bytes(20));
        $this->setToken($token);
        return $token;
    }

    /**
     * Supprime une valeur de session
     *
     * @param null $key
     */
    public function delete($key = null)
    {
        $this->start();
        if ($_SESSION) {
            unset($_SESSION[$key]);
        }
    }

    /**
     * Retourne l'iD de session
     *
     * @return string
     * @throws SessionException
     */
    public function getId()
    {
        if (!$this->isSession) {
            throw new \Exception('La session n\'est pas démarré pour la lecture de l\'ID');
        }
        return session_id();
    }

    /**
     * Regénere un id de session
     *
     * @param bool $destroy
     */
    public function regenerate($destroy = false)
    {
        $this->start();
        if ($this->isSessionRegenerate) {
            return;
        }
        session_regenerate_id($destroy);

        $this->isSessionRegenerate = true;
    }

    /**
     * Détruit la session
     */
    public function destroy()
    {
        $this->start();
        if (session_id()) {
            session_destroy();
        }
    }
}
