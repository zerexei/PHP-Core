<?php

namespace Zerexei\PHPCore\Http\Traits;

trait Route
{
    /**
     * Load a routes file and register its routes against this router.
     * The file must return a callable that accepts a Router instance.
     *
     * @throws \Exception if the file does not exist.
     */
    public function load(string $file): static
    {
        if (!file_exists($file)) {
            throw new \Exception(
                sprintf('Routes file "%s" does not exist.', $file)
            );
        }

        $routes = require_once $file;
        $routes($this);
        return $this;
    }

    /**
     * Register a GET route.
     */
    public function get(string $url, array|callable $controller): void
    {
        $this->addRoute('GET', $url, $controller);
    }

    /**
     * Register a POST route.
     */
    public function post(string $url, array|callable $controller): void
    {
        $this->addRoute('POST', $url, $controller);
    }

    /**
     * Register a PATCH route.
     */
    public function patch(string $url, array|callable $controller): void
    {
        $this->addRoute('PATCH', $url, $controller);
    }

    /**
     * Register a PUT route.
     */
    public function put(string $url, array|callable $controller): void
    {
        $this->addRoute('PUT', $url, $controller);
    }

    /**
     * Register a DELETE route.
     */
    public function delete(string $url, array|callable $controller): void
    {
        $this->addRoute('DELETE', $url, $controller);
    }
}
