<?php

namespace Zeretei\PHPCore\Blueprint;

/**
 * Controller base class
 */
abstract class Controller
{

    /**
     * Current router controller action executed
     */
    protected string $action;

    /**
     * @var \Zeretei\PHPCore\Blueprint\Middleware
     */
    protected array $middlewares = [];

    /**
     * Throw an error when a method does not exist
     */
    public function __call($method, $parameters)
    {
        throw new \Exception(sprintf(
            'Method: "%s()" does not exist on %s.',
            $method,
            static::class
        ));
    }

    /**
     * Register a middleware
     */
    protected function registerMiddleware($middleware)
    {
        $this->middlewares[] = $middleware;
    }

    /**
     * Set router controller action
     */
    public function setAction(string $action): void
    {
        $this->action = $action;
    }

    /**
     * Return all the registered middlewares
     */
    public function getMiddlewares(): array
    {
        return $this->middlewares;
    }
}
