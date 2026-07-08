<?php

namespace Zeretei\PHPCore\Blueprint;

/**
 * Base class for all controllers.
 *
 * Provides middleware registration and action tracking.
 * Extend this class and define public action methods that the Router can dispatch.
 */
abstract class Controller
{
    /**
     * The controller action currently being dispatched by the Router.
     */
    protected string $action = '';

    /**
     * Middlewares registered for this controller instance.
     *
     * @var list<Middleware>
     */
    protected array $middlewares = [];

    /**
     * Throw a descriptive exception when an undefined method is called.
     *
     * @param list<mixed> $parameters
     * @throws \Exception always
     */
    public function __call(string $method, array $parameters): never
    {
        throw new \Exception(sprintf(
            'Method "%s()" does not exist on %s.',
            $method,
            static::class
        ));
    }

    /**
     * Register a middleware to run before this controller's actions.
     * Pass action names to the Middleware constructor to scope it to specific actions.
     */
    protected function registerMiddleware(Middleware $middleware): void
    {
        $this->middlewares[] = $middleware;
    }

    /**
     * Set the action name being dispatched (called by the Router before dispatch).
     */
    public function setAction(string $action): void
    {
        $this->action = $action;
    }

    /**
     * Return all registered middlewares for this controller.
     *
     * @return list<Middleware>
     */
    public function getMiddlewares(): array
    {
        return $this->middlewares;
    }
}
