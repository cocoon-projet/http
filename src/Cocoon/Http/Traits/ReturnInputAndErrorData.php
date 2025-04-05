<?php
declare(strict_types=1);

namespace Cocoon\Http\Traits;

use Cocoon\Http\Facades\Request;
use Cocoon\Http\Facades\Session;

/**
 * Trait pour la gestion des entrées et des erreurs dans les redirections
 * 
 * Ce trait fournit des méthodes pour persister les données de formulaire
 * et les messages d'erreur lors des redirections.
 */
trait ReturnInputAndErrorData
{
    /**
     * Enregistre les erreurs en session
     *
     * @param array|string $errors Tableau d'erreurs ou message d'erreur unique
     * @param string|null $key Clé spécifique pour l'erreur (optionnel)
     * @return $this
     */
    public function withErrors($errors = [], ?string $key = null)
    {
        if (is_string($errors)) {
            $errors = [$errors];
        }

        if ($key !== null) {
            Session::set('errors.' . $key, $errors);
        } else {
            Session::set('errors', $errors);
        }

        return $this;
    }

    /**
     * Enregistre une erreur unique en session
     *
     * @param string $message Message d'erreur
     * @param string|null $key Clé spécifique pour l'erreur
     * @return void
     */
    public function withError(string $message, ?string $key = null): void
    {
        $this->withErrors($message, $key);
    }

    /**
     * Enregistre les données d'entrée en session pour réaffichage
     *
     * @param array|null $input Données à sauvegarder (null pour utiliser les données POST actuelles)
     * @param array $except Tableau des champs à exclure
     * @return $this
     */
    public function withInput(?array $input = null, array $except = [])
    {
        $input = $input ?? Request::getParsedBody();

        // Filtrer les champs sensibles par défaut
        $except = array_merge($except, ['password', 'password_confirmation']);

        foreach ($input as $key => $value) {
            if (!in_array($key, $except, true)) {
                Session::setInput($key, $value);
            }
        }

        return $this;
    }

    /**
     * Enregistre uniquement les champs spécifiés
     *
     * @param array $only Tableau des champs à sauvegarder
     * @return void
     */
    public function withOnly(array $only): void
    {
        $input = Request::getParsedBody();
        $filtered = array_intersect_key($input, array_flip($only));
        
        $this->withInput($filtered);
    }

    /**
     * Enregistre tous les champs sauf ceux spécifiés
     *
     * @param array $except Tableau des champs à exclure
     * @return void
     */
    public function withoutInput(array $except): array
    {
        $input = Request::getParsedBody();
        $filtered = array_diff_key($input, array_flip($except));
        $this->withInput($filtered);
        return $filtered;
    }

    /**
     * Enregistre les données d'entrée et les erreurs en une seule fois
     *
     * @param array $errors Tableau d'erreurs
     * @param array|null $input Données à sauvegarder (null pour utiliser les données POST actuelles)
     * @return void
     */
    public function withErrorsAndInput(array $errors, ?array $input = null): array
    {
        $this->withErrors($errors);
        $this->withInput($input);
        return ['errors' => $errors, 'input' => $input ?? Request::getParsedBody()];
    }

    /**
     * Vérifie si des erreurs ont été enregistrées en session
     *
     * @param string|null $key Clé spécifique à vérifier
     * @return bool
     */
    public function hasErrors(?string $key = null): bool
    {
        if ($key !== null) {
            return Session::has('errors.' . $key);
        }

        return Session::has('errors');
    }

    /**
     * Récupère les erreurs enregistrées en session
     *
     * @param string|null $key Clé spécifique à récupérer
     * @return array
     */
    public function getErrors(?string $key = null): array
    {
        if ($key !== null) {
            return Session::get('errors.' . $key) ?? [];
        }

        return Session::get('errors') ?? [];
    }
}
