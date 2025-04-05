[![Tests](https://github.com/cocoon-projet/http/actions/workflows/ci.yml/badge.svg)](https://github.com/cocoon-projet/http/actions/workflows/ci.yml) [![codecov](https://codecov.io/gh/cocoon-projet/http/graph/badge.svg?token=AX0056E45N)](https://codecov.io/gh/cocoon-projet/http) ![License](https://img.shields.io/badge/Licence-MIT-green)

# Composant HTTP avec Protection CSRF

Ce composant PHP fournit une implémentation robuste pour la gestion des requêtes HTTP et la protection CSRF (Cross-Site Request Forgery). Il est construit selon les standards PSR-7 et PSR-15, offrant une solution moderne et sécurisée pour vos applications web.

## Caractéristiques

- 🛡️ Protection CSRF intégrée
- 🔄 Middleware Pipeline PSR-15
- 🎯 Routage simple et efficace
- 🔒 Gestion sécurisée des sessions
- ✨ Façades pour une utilisation simplifiée
- 🧪 Tests unitaires complets

## Installation

```bash
composer require cocoon-projet/http
```

## Configuration requise

- PHP 7.4 ou supérieur
- Composer
- Extension PHP session activée

## Utilisation rapide

### 1. Configuration de base

```php
use Cocoon\Http\Application;
use Cocoon\Http\Facades\Request;
use Cocoon\Http\Middleware\CsrfMiddleware;

// Démarrer la session
session_start();

// Créer l'application
$app = new Application();

// Initialiser la requête
$request = Request::init();
```

### 2. Protection CSRF

```php
// Configuration du middleware CSRF
$excludedPaths = [
    '#^/api/webhook#',
    '#^/api/external#'
];
$csrfMiddleware = new CsrfMiddleware($excludedPaths);

// Ajouter le middleware à l'application
$app->add($csrfMiddleware);
```

### 3. Utilisation dans un formulaire

```html
<form method="POST" action="/submit">
    <!-- Ajouter le token CSRF -->
    <input type="hidden" name="csrf_token" value="<?php echo Session::get('token')[0]['value'] ?? ''; ?>">
    <!-- Vos champs de formulaire -->
</form>
```

## Architecture

### Composants principaux

1. **Application**
   - Gère le pipeline de middleware
   - Traite les requêtes entrantes

2. **HttpRequest**
   - Implémente PSR-7 ServerRequestInterface
   - Gère les données de requête ($_GET, $_POST, etc.)

3. **Middleware**
   - CsrfMiddleware : Protection contre les attaques CSRF
   - RouterMiddleware : Gestion des routes
   - NotFoundMiddleware : Gestion des pages 404

4. **Façades**
   - Request : Accès simplifié aux données de requête
   - Session : Gestion des sessions
   - Response : Création et envoi de réponses

## Exemples d'utilisation

### Récupération des données de requête

```php
// Données GET
$value = Request::query('param');

// Données POST
$value = Request::input('field');

// Sélection de champs spécifiques
$data = Request::only(['username', 'email']);
```

### Gestion des sessions

```php
use Cocoon\Http\Facades\Session;

// Définir une valeur
Session::set('key', 'value');

// Récupérer une valeur
$value = Session::get('key');

// Message flash
Session::setFlash('success', 'Opération réussie');
```

## Tests

Pour exécuter les tests unitaires :

```bash
vendor/bin/phpunit
```

## Sécurité

Le composant intègre plusieurs mesures de sécurité :

- Protection CSRF automatique
- Validation des tokens
- Nettoyage des données de session
- Messages d'erreur sécurisés

## Contribution

Les contributions sont les bienvenues ! Veuillez :

1. Fork le projet
2. Créer une branche pour votre fonctionnalité
3. Commiter vos changements
4. Pousser vers la branche
5. Créer une Pull Request

## Licence

MIT License. Voir le fichier `LICENSE` pour plus de détails.

## Support

Pour toute question ou problème :

1. Consultez la documentation
2. Ouvrez une issue sur GitHub
3. Contactez l'équipe de développement

---

Développé avec ❤️ par l'équipe Cocoon