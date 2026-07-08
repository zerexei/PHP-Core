<?php

namespace Zeretei\PHPCore\Http\Traits;

trait RouterController
{
    /**
     * Named wildcard patterns used in route definitions.
     * Each key is the placeholder token; the value is its regex capture group.
     *
     * @var array<string, string>
     */
    protected array $patterns = [
        ':int'  => '(\d+)',
        ':char' => '([a-zA-Z]+)',
        ':str'  => '(\w+)',
        ':any'  => '(.+)',
    ];

    /**
     * Return only the routes that contain at least one wildcard token.
     *
     * @param array<string, array|callable> $routes
     * @return array<string, array|callable>
     */
    protected function getRoutesWithWildcard(array $routes): array
    {
        return array_filter(
            $routes,
            fn (mixed $_, string $route) => str_contains($route, ':'),
            ARRAY_FILTER_USE_BOTH
        );
    }

    /**
     * Attempt to match a wildcard route pattern against the request URI.
     * On a match, captured segments are stored in $this->attributes.
     */
    protected function matchWildcard(string $route, string $url): bool
    {
        $regex = str_replace(
            array_keys($this->patterns),
            array_values($this->patterns),
            $route
        );

        if (preg_match("#^{$regex}$#", $url, $values)) {
            $this->attributes = array_slice($values, 1);
            return true;
        }

        return false;
    }

    /**
     * Instantiate the controller, run applicable middlewares, and invoke the action.
     *
     * @param array{0: class-string, 1?: string} $controller
     */
    protected function callAction(array $controller): mixed
    {
        [$class, $action] = [...$controller, null];

        if (!class_exists($class)) {
            throw new \Exception(
                sprintf('Controller "%s" does not exist.', $class)
            );
        }

        $instance = new $class();

        if (is_null($action)) {
            return $this->callInvoke($instance);
        }

        $instance->setAction($action);

        foreach ($instance->getMiddlewares() as $middleware) {
            if ($middleware->shouldExecute($action)) {
                $middleware->execute($action, ...$this->attributes);
            }
        }

        return $instance->$action(...$this->attributes);
    }

    /**
     * Invoke a single-action controller via its __invoke method.
     */
    protected function callInvoke(object $instance): mixed
    {
        if (!is_callable($instance)) {
            throw new \Exception(
                sprintf('"%s" does not implement __invoke().', $instance::class)
            );
        }

        return $instance();
    }
}
