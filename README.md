# PHP Core

<p align="center">
  A lightweight, modern, and zero-dependency PHP MVC micro-framework.
</p>

<p align="center">
  <a href="https://github.com/zerexei/PHP-Core/blob/main/LICENSE">
    <img src="https://img.shields.io/badge/license-MIT-blue.svg" alt="MIT License" />
  </a>
  <img src="https://img.shields.io/badge/PHP-%3E%3D%208.2-777bb4.svg" alt="PHP Version Compatibility" />
  <img src="https://img.shields.io/badge/Version-0.2.0-blue.svg" alt="Version 0.2.0" />
  <img src="https://img.shields.io/badge/PSR--4-compliant-brightgreen.svg" alt="PSR-4 Compliant" />
</p>

---

PHP Core is a minimal yet powerful PHP MVC micro-framework designed to be clean, easy to understand, and highly extensible. It provides a simple IoC container, a robust router, a safe database wrapper, model abstractions, middleware support, and session flash bags.

## Features

- ⚡ **Lightweight IoC Container**: Easy service binding and resolution.
- 🛣️ **Flexible Routing**: Supports GET, POST, PUT, PATCH, DELETE, and wildcards.
- 🛡️ **Auto-Sanitized Input**: Recursive HTML sanitization for requests.
- 💾 **Query Builder & Migrations**: Safe database operations using PDO with automated schema migrations.
- 🔑 **Model & Controller Blueprints**: Built-in base Model (with fillable validation) and Controller.
- ✉️ **Flash & Error Bags**: Session-based notification alerts and validation message stores.
- 🐧 **Linux-Friendly PSR-4 Autoloading**: Standardized folder names ensuring compatibility across case-sensitive filesystems.

---

## Installation & Setup

Add PHP Core to your project via Composer or clone this repository. Define your namespaces in your `composer.json` using PSR-4:

```json
{
    "autoload": {
        "psr-4": {
            "Zeretei\\PHPCore\\": "src/",
            "App\\": "app/"
        },
        "files": [
            "src/helpers.php"
        ]
    }
}
```

### Bootstrap Script

Boot the application using the following structure in your entry point (e.g., `public/index.php`):

```php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

use Zeretei\PHPCore\Application;
use Zeretei\PHPCore\Http\Request;

require_once __DIR__ . '/../vendor/autoload.php';

// Load configuration
$config = require_once __DIR__ . '/../config.php';

// Instantiate the application
$app = new Application($config);

// Bind paths
$app->bind('path.app', $app->ROOT_DIR . '/app');
$app->bind('path.views', $app->ROOT_DIR . '/app/Views');
$app->bind('path.routes', $app->ROOT_DIR . '/app/routes.php');
$app->bind('path.databases', $app->ROOT_DIR . '/app/Databases');

// Run the application
$app->run(Request::uri(), Request::method());
```

---

## Reference & Usage

### 📦 Application Container

The `Application` class extends the `Container` to resolve registered services from anywhere in the codebase:

```php
use Zeretei\PHPCore\Application;

// Retrieve registered services
$config   = Application::get('config');   // Array configuration
$router   = Application::get('router');   // Router instance
$request  = Application::get('request');  // Request instance
$response = Application::get('response'); // Response instance
$database = Application::get('database'); // Database (QueryBuilder) instance
$session  = Application::get('session');  // Session instance
$log      = Application::get('log');      // Logger instance
```

---

### 🛣️ Routing

Define your routes in `app/routes.php`. You can map paths to closures or controller actions. It also supports parameters (wildcards):

```php
// app/routes.php
use Zeretei\PHPCore\Http\Router;
use App\Controller\UserController;

return function (Router $router) {
    // Set Host / Prefix
    $router->setHost('mywebsite');

    // Closure route
    $router->get('/hello', fn () => 'Hello World!');

    // Controller actions (Array callback style)
    $router->get('/users', [UserController::class, 'index']);
    $router->post('/users', [UserController::class, 'store']);
    
    // Route wildcards/parameters (:int, :char, :str, :any)
    $router->put('/user/:int', [UserController::class, 'update']);
    $router->patch('/user/:int', [UserController::class, 'updateField']);
    $router->delete('/user/:int', [UserController::class, 'destroy']);
};
```

---

### 💾 Database Query Builder

Query the database directly using the registered query builder service, which utilizes parameterized PDO statements for SQL injection prevention:

```php
use Zeretei\PHPCore\Application;

$db = Application::get('database');

// Execute arbitrary parameters
$db->query("INSERT INTO users (name, email) VALUES (?, ?)", ['John Doe', 'john@example.com']);

// Fetch a single record
$user = $db->fetch("SELECT * FROM users WHERE id = ?", [1]);

// Fetch all records
$users = $db->fetchAll("SELECT * FROM users WHERE status = ?", ['active']);

// Count rows
$count = $db->count("SELECT * FROM users");
```

---

### 📐 Abstractions: Model, Controller & Middleware

#### 1. Models
Extend the base `Model` class. Define the `$table` and `$fillable` columns to filter database fields automatically:

```php
use Zeretei\PHPCore\Blueprint\Model;

class User extends Model
{
    // If not set, table defaults to plural class name (e.g. "users")
    protected string $table = 'users';
    
    // Whitelisted columns for write operations
    protected array $fillable = ['name', 'email', 'password'];
}

// Usage:
$userModel = new User();

// Insert record (automatically filters keys not present in $fillable)
$userModel->insert([
    'name' => 'Jane Doe',
    'email' => 'jane@example.com',
    'ignored_key' => 'hacky_value' // will be filtered out
]);

// Read & Delete
$user = $userModel->select(1); // Find by primary key
$allUsers = $userModel->all(); // Get all records
$userModel->update(1, ['name' => 'Jane Updated']);
$userModel->delete(1);
```

#### 2. Controllers
Extend the `Controller` class to encapsulate your request-handling logic and register action-specific middlewares:

```php
use Zeretei\PHPCore\Blueprint\Controller;
use App\Middleware\AuthMiddleware;

class UserController extends Controller
{
    public function __construct()
    {
        // Protect the 'delete' action with AuthMiddleware
        $this->registerMiddleware(new AuthMiddleware(['delete']));
    }

    public function delete(int $id)
    {
        // Delete user logic...
    }
}
```

#### 3. Middlewares
Extend the base `Middleware` class and implement the `execute` method:

```php
use Zeretei\PHPCore\Blueprint\Middleware;
use Zeretei\PHPCore\Application;

class AuthMiddleware extends Middleware
{
    public function execute(string $action)
    {
        // Simple session guard
        if (Application::get('session')->get('user') === null) {
            Application::get('response')->redirect('/login');
        }
    }
}
```

---

## License

This project is open-source software licensed under the [MIT License](LICENSE).
