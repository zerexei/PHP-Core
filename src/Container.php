<?php

namespace Zeretei\PHPCore;

/**
 * Service container for Application 
 */
class Container
{
    /**
     * Container instance
     */
    protected static $instance;

    /**
     * services placeholder
     */
    protected static array $registry = [];

    /**
     * Register a service to the container
     */
    public static function bind(string $key, mixed $value): void
    {
        static::$registry[$key] = $value;
    }

    /**
     * Get a service from the container
     */
    public static function get(string $key): mixed
    {
        if (!array_key_exists($key, static::$registry)) {
            throw new \Exception(
                sprintf('No "%s" is registered in the container.', $key)
            );
        }

        return static::$registry[$key];
    }

    /**
     * check if the $key exists in the container
     */
    public static function has(string $key): bool
    {
        return array_key_exists($key, static::$registry);
    }

    /**
     * Get all the registered services
     */
    public static function all()
    {
        return static::$registry;
    }

    /**
     * Get the instance of the Container
     */
    public static function getInstance()
    {
        if (is_null(static::$instance)) {
            static::$instance = new static;
        }

        return static::$instance;
    }
}
