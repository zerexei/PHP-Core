<?php

namespace Zeretei\PHPCore\Http\Traits;

trait Route
{
    /**
     * Load routes file
     */
    public function load(string $file)
    {
        if (!file_exists($file)) {
            throw new \Exception(
                sprintf('File: "%s" does not exist.', $file)
            );
        }

        $routes = require_once $file;
        $routes($this);
        return $this;
    }

    /**
     * Get request
     */
    public function get(string $url, array|callable $controller): void
    {
        $this->addRoute('GET', $url, $controller);
    }

    /**
     * Post request
     */
    public function post(string $url, array|callable $controller): void
    {
        $this->addRoute('POST', $url, $controller);
    }

    /**
     * PATCH request
     */
    public function patch(string $url, array|callable $controller): void
    {
        $this->addRoute('PATCH', $url, $controller);
    }

    /**
     * PUT request
     */
    public function put(string $url, array|callable $controller): void
    {
        $this->addRoute('PUT', $url, $controller);
    }

    /**
     * DELETE request
     */
    public function delete(string $url, array|callable $controller): void
    {
        $this->addRoute('DELETE', $url, $controller);
    }
}
