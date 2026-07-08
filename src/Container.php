<?php

namespace Zerexei\PHPCore;

/**
 * Lightweight service container / service locator.
 *
 * All registered services are stored statically so they are accessible from
 * anywhere via Application::get() without needing to thread the container
 * instance through every call site.
 */
class Container
{
    /**
     * The most-recently constructed Container (or Application) instance.
     */
    protected static ?self $instance = null;

    /**
     * Registered services keyed by their binding name.
     *
     * @var array<string, mixed>
     */
    protected static array $registry = [];

    /**
     * Bind a service under the given key.
     * Overwrites any existing binding for that key.
     */
    public static function bind(string $key, mixed $value): void
    {
        static::$registry[$key] = $value;
    }

    /**
     * Retrieve a registered service by key.
     *
     * @throws \Exception when no service is bound to $key.
     */
    public static function get(string $key): mixed
    {
        if (!array_key_exists($key, static::$registry)) {
            throw new \Exception(
                sprintf('No service "%s" is registered in the container.', $key)
            );
        }

        return static::$registry[$key];
    }

    /**
     * Return whether a service is registered under $key.
     */
    public static function has(string $key): bool
    {
        return array_key_exists($key, static::$registry);
    }

    /**
     * Return all registered services.
     *
     * @return array<string, mixed>
     */
    public static function all(): array
    {
        return static::$registry;
    }

    /**
     * Return the current container instance, creating one lazily if needed.
     */
    public static function getInstance(): static
    {
        if (is_null(static::$instance)) {
            static::$instance = new static();
        }

        return static::$instance;
    }
}
