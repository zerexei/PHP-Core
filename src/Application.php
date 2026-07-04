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
 * TODO:
 * 1. Add events
 * 2. use proper exception w/ code
 * 
 * Application base class
 */
class Application extends Container
{
    /**
     * Application version
     * 
     * @var string
     */
    public const VERSION = '0.1.0';

    /**
     * Application root directory
     * 
     * @var string
     */
    public string $ROOT_DIR = '/';

    /**
     * Register all the application default configs & services
     */
    public function __construct(?array $config = null)
    {
        static::$instance = $this;
        $config = $config ?? [];
        $this->ROOT_DIR = $config['root_dir'] ?? '/';
        $this->registerServices($config);
    }

    /**
     * Run application
     */
    public function run(string $uri, string $method)
    {
        try {
            // start routing
            ob_start();

            $this->get('router')
                ->load($this->get('path.routes'))
                ->resolve($uri, $method);

            echo ob_get_clean();
            exit;

        } catch (\Exception $e) {
            $error = sprintf('[%s] %s  %s',
                $e->getCode(),
                $e->getFile() . PHP_EOL,
                $e->getMessage()
            );

            Log::set($error);

            ddd($e);
        }
    }

    /**
     * Register the services to the container
     */
    protected function registerServices(array $config)
    {
        $this->bind('config', $config);

        $this->bind('router', new Router());
        $this->bind('request', new Request());
        $this->bind('response', new Response());

        $db = $config['database'] ?? [];

        if (!empty($db)) {
            $this->bind('database', new QueryBuilder($db));
        }

        $this->bind('session', new Session());
        $this->bind('log', new Log());
    }
}