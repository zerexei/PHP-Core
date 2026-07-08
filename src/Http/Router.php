<?php

namespace Zeretei\PHPCore\Http;

use \Zeretei\PHPCore\Http\Traits\Route;
use \Zeretei\PHPCore\Http\Traits\RouterController;

class Router
{
    use Route;
    use RouterController;

    /**
     * Supported HTTP verbs.
     */
    protected const VERBS = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'];

    /**
     * Router base path prefix applied to every registered route.
     */
    protected string $host = '/';

    /**
     * Registered routes keyed by HTTP method then URI.
     *
     * @var array<string, array<string, array|callable>>
     */
    protected array $routes = [
        'GET'    => [],
        'POST'   => [],
        'PUT'    => [],
        'PATCH'  => [],
        'DELETE' => [],
    ];

    /**
     * Captured wildcard values from the matched route.
     *
     * @var list<string>
     */
    protected array $attributes = [];

    /**
     * Match the current URI + method against registered routes and dispatch.
     */
    public function resolve(string $uri, string $method): mixed
    {
        $method = strtoupper($method);

        // Support HTML-form method spoofing via hidden _method field.
        if ($method === 'POST' && isset($_POST['_method'])) {
            $method = strtoupper($_POST['_method']);
        }

        if (!$this->isValidVerb($method)) {
            throw new \Exception(
                sprintf('Request method "%s" is not supported.', $method)
            );
        }

        $callback = $this->routes[$method][$uri] ?? null;

        if (is_null($callback)) {
            foreach ($this->getRoutesWithWildcard($this->routes[$method]) as $route => $action) {
                if ($this->matchWildcard($route, $uri)) {
                    $callback = $action;
                    break;
                }
            }
        }

        if (is_null($callback)) {
            Response::setStatusCode(404);
            throw new \Exception(
                sprintf('No route matched "%s %s".', $method, $uri)
            );
        }

        if (is_callable($callback)) {
            return $callback(...$this->attributes);
        }

        return $this->callAction($callback);
    }

    /**
     * Register a route for the given HTTP method.
     */
    protected function addRoute(string $method, string $url, array|callable $controller): void
    {
        $url = trim($this->host . '/' . ltrim($url, '/'), '/');
        $url = (string) filter_var($url, FILTER_SANITIZE_URL);

        $this->routes[$method][$url] = $controller;
    }

    /**
     * Return whether the given method string is a supported HTTP verb.
     */
    public function isValidVerb(string $method): bool
    {
        return in_array($method, static::VERBS, true);
    }

    /**
     * Set the base path prefix prepended to every registered route URL.
     */
    public function setHost(string $path): void
    {
        $this->host = $path;
    }

    /**
     * Redirect the client to the previous URL (HTTP_REFERER).
     * Does nothing if headers have already been sent or no referer is set.
     */
    public function back(): void
    {
        if (!headers_sent() && isset($_SERVER['HTTP_REFERER'])) {
            header('Location: ' . $_SERVER['HTTP_REFERER'], true, 302);
            exit;
        }
    }
}
