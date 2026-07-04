<?php

namespace Zeretei\PHPCore\Http;

use \Zeretei\PHPCore\Http\Traits\Route;
use \Zeretei\PHPCore\Http\Traits\RouterController;

class Router
{
    use Route;
    use RouterController;

    /**
     * Router host
     * 
     * @var string
     */
    protected static $host = '/';

    /**
     * Routes placeholder
     */
    protected static array $routes = [
        'GET' => [],
        'POST' => [],
        'PUT' => [],
        'PATCH' => [],
        'DELETE' => [],
    ];

    /**
     * Routes available request methods
     */
    protected static array $verbs = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'];



    /**
     * Router attributes placeholder
     */
    protected array $attributes = [];

    /**
     * Match the current url with the defined routes
     */
    public function resolve(string $uri, string $method): mixed
    {
        $method = $_POST["_method"] ?? $method;

        if (!$this->isValidVerb($method)) {
            throw new \Exception(
                sprintf('Request Method: "%s" is not a valid method.', $method)
            );
        }

        $callback = static::$routes[$method][$uri] ?? null;

        if (is_null($callback)) {
            $routesWithWildcard = $this->getRoutesWithWildcard(static::$routes[$method]);

            foreach ($routesWithWildcard as $route => $action) {
                if ($this->matchWildcard($route, $uri)) {
                    $callback = $action;
                    break;
                }
            }
        }

        if (is_null($callback)) {
            Response::setStatusCode(404);
            throw new \Exception(
                sprintf('Method: "%s" on Route: "%s" is not defined.', $method, $uri)
            );
        }

        if (is_callable($callback)) {
            return $callback(...$this->attributes);
        }

        return $this->callAction($callback);
    }

    /**
     * Add a route
     */
    protected function addRoute(string $method, string $url, array|callable $controller): void
    {
        $url = trim(static::$host . $url, "/");
        $url = filter_var($url, FILTER_SANITIZE_URL);

        if (is_array($controller)) {
            $controller = array_map(
                fn ($item) => strip_tags(htmlspecialchars((string) $item, ENT_QUOTES, 'UTF-8')),
                $controller
            );
        }

        self::$routes[$method][$url] = $controller;
    }

    /**
     * Check if the request method is a valid verb.
     */
    public function isValidVerb(string $method): bool
    {
        return in_array($method, static::$verbs);
    }

    /**
     * Set the router host
     */
    public function setHost(string $path)
    {
        static::$host = $path;
    }

    /**
     * Redirect back to previous url
     */
    public function back(): void
    {
        // check if previous uri exist
        if (!headers_sent() && isset($_SERVER["HTTP_REFERER"])) {
            // redirect to previous url
            header("location: {$_SERVER["HTTP_REFERER"]}", true, 302);
            exit;
        }
    }
}
