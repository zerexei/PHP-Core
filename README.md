# PHP Core

<p align="center">
  A lightweight, zero-dependency PHP MVC micro-framework.
</p>

<p align="center">
    <img src="https://img.shields.io/packagist/v/zeretei/php-core.svg?style=flat-square" alt="Latest Version on Packagist" />
    <img src="https://img.shields.io/packagist/dt/zeretei/php-core.svg?style=flat-square" alt="Total Downloads" />
    <img src="https://img.shields.io/badge/license-MIT-blue.svg?style=flat-square" alt="MIT License" />
    <img src="https://img.shields.io/badge/php-%3E%3D%208.2-777bb4.svg?style=flat-square" alt="PHP 8.2+" />
    <img src="https://img.shields.io/badge/PSR--4-compliant-brightgreen.svg?style=flat-square" alt="PSR-4 Compliant" />
</p>

---

A minimal PHP 8.2+ MVC micro-framework with a service container, router, PDO query builder, model abstractions, middleware pipeline, and session flash bags. Zero runtime dependencies.

## Features

- ⚡ **Lightweight IoC Container** — Simple service binding and resolution via a static registry.
- 🛣️ **Flexible Routing** — GET, POST, PUT, PATCH, DELETE with typed wildcard parameters (`:int`, `:str`, `:char`, `:any`).
- 🛡️ **Auto-Sanitized Input** — Recursive HTML-entity encoding on all request input at construction time.
- 💾 **Query Builder & Migrations** — Parameterized PDO statements and a file-based migration runner with an applied-migrations table.
- 🔑 **Model Blueprints** — Base `Model` with a `$fillable` mass-assignment guard and built-in CRUD.
- 🎛️ **Controller & Middleware Pipeline** — Base `Controller` with per-action middleware scoping via `shouldExecute()`.
- ✉️ **Flash & Error Bags** — Two-pass session flash bags: one for notifications, one for validation errors.
- 🐧 **PSR-4 Autoloading** — Standardized, case-sensitive-filesystem-safe folder names.

## Table of Contents

- [Installation](#installation)
- [Project Structure](#project-structure)
- [Bootstrap](#bootstrap)
- [Configuration](#configuration)
- [Container](#container)
- [Routing](#routing)
- [Request & Validation](#request--validation)
- [Response](#response)
- [Database](#database)
- [Models](#models)
- [Controllers](#controllers)
- [Middleware](#middleware)
- [Session](#session)
- [Migrations](#migrations)
- [License](#license)

---

## Installation

Require via Composer:

```bash
composer require zeretei/php-core
```

Then add your application namespace to your project's `composer.json`:

```json
{
    "autoload": {
        "psr-4": {
            "App\\": "app/"
        }
    }
}
```

```bash
composer dump-autoload
```

---

## Project Structure

A typical project using PHP Core looks like this:

```
project/
├── app/
│   ├── Controllers/
│   ├── Middleware/
│   ├── Models/
│   ├── Databases/
│   │   └── migrations/
│   ├── Views/
│   └── routes.php
├── public/
│   └── index.php          # Entry point
├── config.php
└── composer.json
```

---

## Bootstrap

Create `public/index.php` as the single entry point:

```php
<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

use Zeretei\PHPCore\Application;
use Zeretei\PHPCore\Http\Request;

$config = require_once __DIR__ . '/../config.php';

$app = new Application($config);

$app->bind('path.app',       $app->ROOT_DIR . '/app');
$app->bind('path.views',     $app->ROOT_DIR . '/app/Views');
$app->bind('path.routes',    $app->ROOT_DIR . '/app/routes.php');
$app->bind('path.databases', $app->ROOT_DIR . '/app/Databases');

$app->run(Request::uri(), Request::method());
```

---

## Configuration

Create `config.php` in your project root:

```php
<?php

return [
    'root_dir' => dirname(__DIR__),

    'database' => [
        // Option A — explicit DSN (recommended)
        'dsn'      => 'mysql:host=127.0.0.1;dbname=myapp;charset=utf8mb4',
        'username' => 'root',
        'password' => '',

        // Option B — constructed DSN (legacy)
        // 'connection' => 'mysql:host=127.0.0.1',
        // 'name'       => 'myapp',
        // 'username'   => 'root',
        // 'password'   => '',
    ],
];
```

---

## Container

`Application` extends `Container`, giving you a static service registry accessible from anywhere:

```php
use Zeretei\PHPCore\Application;

$config   = Application::get('config');    // array
$router   = Application::get('router');    // Router
$request  = Application::get('request');   // Request
$response = Application::get('response');  // Response
$database = Application::get('database');  // QueryBuilder
$session  = Application::get('session');   // Session
$log      = Application::get('log');       // Log
```

Bind your own services the same way:

```php
$app->bind('mailer', new Mailer($config));

// Later, anywhere:
Application::get('mailer')->send($message);
```

---

## Routing

Define routes in `app/routes.php`. The file must return a callable that receives the `Router` instance:

```php
<?php

// app/routes.php
use Zeretei\PHPCore\Http\Router;
use App\Controllers\UserController;

return function (Router $router): void {
    // Optional base-path prefix for every route
    $router->setHost('mysite');

    // Closure route
    $router->get('/ping', fn () => 'pong');

    // Controller action  [ClassName::class, 'method']
    $router->get('/users',      [UserController::class, 'index']);
    $router->post('/users',     [UserController::class, 'store']);
    $router->put('/users/:int', [UserController::class, 'update']);
    $router->patch('/users/:int', [UserController::class, 'patch']);
    $router->delete('/users/:int', [UserController::class, 'destroy']);
};
```

### Wildcard tokens

| Token   | Matches         | Example       |
| ------- | --------------- | ------------- |
| `:int`  | Digits only     | `42`          |
| `:char` | Letters only    | `abc`         |
| `:str`  | Word characters | `hello_world` |
| `:any`  | Anything        | `foo/bar/baz` |

Captured values are passed as positional arguments to the action:

```php
// Route: /users/:int
public function update(int $id): void { ... }
```

### HTML form method spoofing

HTML forms only support GET and POST. For PUT/PATCH/DELETE, add a hidden field:

```html
<form method="POST" action="/users/42">
    <input type="hidden" name="_method" value="DELETE" />
    ...
</form>
```

---

## Request & Validation

### Accessing input

```php
use Zeretei\PHPCore\Application;

$request = Application::get('request');

// All sanitized input
$all  = $request->all();

// Individual attribute (HTML-sanitized)
$name = $request->name;

// Raw query string
$page = Request::query('page');
```

### Validating input

Call `$request->validate()` inside a controller action. On failure it automatically redirects back with session errors — on success it returns the validated values:

```php
public function store(): void
{
    $data = $this->request->validate([
        'name'     => 'required|min:2|max:100',
        'email'    => 'required|email',
        'password' => 'required|min:8|confirm',
    ]);

    // $data is only reached when all rules pass
    (new User())->insert($data);
}
```

#### Available rules

| Rule         | Description                                                      |
| ------------ | ---------------------------------------------------------------- |
| `required`   | Must be non-empty                                                |
| `string`     | Must be a string                                                 |
| `email`      | Must be a valid email address                                    |
| `min:N`      | Minimum N characters                                             |
| `max:N`      | Maximum N characters                                             |
| `same:field` | Must equal the value of another field                            |
| `confirm`    | Must equal `{field}_confirmation` (e.g. `password_confirmation`) |

---

## Response

```php
use Zeretei\PHPCore\Application;

$response = Application::get('response');

// Redirect
$response->redirect('/dashboard');
$response->redirect('/login', 301);

// Set status code only
Response::setStatusCode(404);
```

---

## Database

The `QueryBuilder` wraps PDO with parameterized statements. All queries use bound parameters — no raw string interpolation.

```php
use Zeretei\PHPCore\Application;

$db = Application::get('database');

// INSERT / UPDATE / DELETE → returns bool
$db->query('INSERT INTO users (name, email) VALUES (?, ?)', ['Jane', 'jane@example.com']);

// Single row → returns array|false
$user = $db->fetch('SELECT * FROM users WHERE id = ?', [1]);

// All rows → returns array
$users = $db->fetchAll('SELECT * FROM users WHERE active = ?', [1]);

// Row count
$total = $db->count('SELECT * FROM users');

// Raw DDL
$db->execute('CREATE TABLE ...');
```

---

## Models

Extend `Model`, declare `$fillable`, and get CRUD for free. Keys not in `$fillable` are silently stripped on write — this is the mass-assignment guard.

```php
<?php

namespace App\Models;

use Zeretei\PHPCore\Blueprint\Model;

class User extends Model
{
    // Defaults to lowercase class name + "s" → "users"
    protected string $table = 'users';

    protected array $fillable = ['name', 'email', 'password'];
}
```

```php
$users = new User();

// Create
$users->insert(['name' => 'Jane', 'email' => 'jane@example.com', 'password' => $hash]);

// Read
$user    = $users->select(1);        // by primary key → array|false
$all     = $users->all();            // all rows

// Update
$users->update(1, ['name' => 'Jane Doe']);

// Delete
$users->delete(1);

// Custom key column
$users->select('jane@example.com', key: 'email');
$users->delete('jane@example.com', key: 'email');
```

---

## Controllers

Extend `Controller` and define public methods that map to route actions. Use `registerMiddleware()` in the constructor to attach middleware — optionally scoped to specific actions.

```php
<?php

namespace App\Controllers;

use Zeretei\PHPCore\Blueprint\Controller;
use App\Middleware\AuthMiddleware;

class UserController extends Controller
{
    public function __construct()
    {
        // AuthMiddleware runs only before 'update', 'patch', and 'destroy'
        $this->registerMiddleware(new AuthMiddleware(['update', 'patch', 'destroy']));
    }

    public function index(): void
    {
        // publicly accessible
    }

    public function update(int $id): void
    {
        // guarded by AuthMiddleware
    }

    public function destroy(int $id): void
    {
        // guarded by AuthMiddleware
    }
}
```

---

## Middleware

Extend `Middleware` and implement `execute()`. Pass action names to the constructor to scope the middleware — an empty array means it runs on every action.

```php
<?php

namespace App\Middleware;

use Zeretei\PHPCore\Blueprint\Middleware;
use Zeretei\PHPCore\Application;

class AuthMiddleware extends Middleware
{
    public function execute(string $action, mixed ...$params): void
    {
        if (Application::get('session')->get('user') === null) {
            Application::get('response')->redirect('/login');
        }
    }
}
```

```php
// Scoped to specific actions only
$this->registerMiddleware(new AuthMiddleware(['update', 'destroy']));

// Runs before every action in the controller
$this->registerMiddleware(new AuthMiddleware());
```

---

## Session

```php
$session = Application::get('session');

// Persistent session values
$session->set('user', $userId);
$user = $session->get('user');

// Flash notifications (survive exactly one redirect)
$session->setFlash('success', 'Profile updated.');
$message = $session->getFlash('success');
$all     = $session->flashBag();   // ['success' => 'Profile updated.']

// Validation error bag (populated automatically by validate())
$session->setErrorFlash('email', 'Invalid email address.');
$error  = $session->getErrorFlash('email');
$errors = $session->errorBag();    // ['email' => 'Invalid email address.']
```

---

## Migrations

### File naming

Migration files live in `app/Databases/migrations/`. Each file name must follow this convention:

```
{numeric_prefix}_{PascalCase_class_name_parts}.php
```

| Filename                      | Expected class     |
| ----------------------------- | ------------------ |
| `0001_create_users_table.php` | `CreateUsersTable` |
| `0002_add_email_to_users.php` | `AddEmailToUsers`  |

### Writing a migration

```php
<?php

// app/Databases/migrations/0001_create_users_table.php

class CreateUsersTable
{
    public function up(): string
    {
        return "CREATE TABLE IF NOT EXISTS users (
            id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name       VARCHAR(100) NOT NULL,
            email      VARCHAR(255) NOT NULL UNIQUE,
            password   VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
    }
}
```

### Running migrations

```php
use Zeretei\PHPCore\Database\Migration;

(new Migration())->apply();
```

Applied migrations are tracked in a `migrations` table so they are never run twice.

---

## License

This project is open-source software licensed under the [MIT License](LICENSE).

