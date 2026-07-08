<?php

namespace Zeretei\PHPCore;

use \Zeretei\PHPCore\Container;

use \Zeretei\PHPCore\Http\Router;
use \Zeretei\PHPCore\Http\Request;
use \Zeretei\PHPCore\Http\Response;

use \Zeretei\PHPCore\Database\QueryBuilder;

use \Zeretei\PHPCore\Session;
use \Zeretei\PHPCore\Log;

/**
 * Application entry point.
 *
 * Extends Container to provide a global service registry.
 * Bootstraps all core services and delegates HTTP dispatch to the Router.
 */
class Application extends Container
{
    /**
     * Framework version.
     */
    public const VERSION = '0.2.0';

    /**
     * Absolute path to the project root directory.
     */
    public string $ROOT_DIR = '/';

    /**
     * Register all default services and boot the application.
     */
    public function __construct(?array $config = null)
    {
        static::$instance = $this;
        $config = $config ?? [];
        $this->ROOT_DIR = $config['root_dir'] ?? '/';
        $this->registerServices($config);
    }

    /**
     * Resolve the current request and dispatch it through the router.
     * Catches any uncaught exception, logs it, and returns a generic 500 response.
     */
    public function run(string $uri, string $method): void
    {
        try {
            ob_start();
            $this->get('router')
                ->load($this->get('path.routes'))
                ->resolve($uri, $method);
            echo ob_get_clean();
        } catch (\Exception $e) {
            ob_end_clean();

            $error = sprintf(
                '[%s] %s' . PHP_EOL . '%s',
                $e->getCode(),
                $e->getFile(),
                $e->getMessage()
            );

            Log::set($error);

            Response::setStatusCode(500);
            echo 'An unexpected error occurred. Please try again later.';
        }
    }

    /**
     * Bind the framework's built-in services to the container.
     */
    protected function registerServices(array $config): void
    {
        $this->bind('config', $config);

        $this->bind('router',   new Router());
        $this->bind('request',  new Request());
        $this->bind('response', new Response());

        $db = $config['database'] ?? [];

        if (!empty($db)) {
            $this->bind('database', new QueryBuilder($db));
        }

        $this->bind('session', new Session());
        $this->bind('log',     new Log());
    }
}