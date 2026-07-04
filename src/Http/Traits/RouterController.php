<?php

namespace Zeretei\PHPCore\Http\Traits;

trait RouterController
{
    /**
     * Available wildcard regex pattern
     */
    protected array $patterns = [
        ":int" => "(\d+)",
        ":char" => "([a-zA-Z]+)",
        ":str" => "(\w+)",
        ":any" => "(.+)",
    ];

    /**
     * Get all routes with wildcard
     */
    protected function getRoutesWithWildcard(array $routes): array
    {
        $hasWildcard = fn ($_, $route) => str_contains($route, ':');
        return array_filter($routes, $hasWildcard, ARRAY_FILTER_USE_BOTH);
    }

    /**
     * Match the request url with the wildcard route
     */
    protected function matchWildcard(string $route, string $url): bool
    {
        $searches = array_keys($this->patterns);
        $replaces = array_values($this->patterns);
        $regex = str_replace($searches, $replaces, $route);

        if (preg_match("#^{$regex}$#", $url, $values)) {
            //! NOTE: this can be bad since it depends on Router class
            //? create an interface to fix this issue
            $this->attributes = array_slice($values, 1);
            return true;
        }

        return false;
    }

    /**
     * Call matched controller action
     */
    protected function callAction(array $controller): mixed
    {
        if (!class_exists($controller[0])) {
            throw new \Exception(
                sprintf('Controller: "%s" does not exist.', $controller[0])
            );
        }

        [$controller, $action] = [...$controller, null];

        $class = new $controller();

        if (is_null($action)) {
            return $this->callInvoke($class);
        }

        $class->setAction($action);

        foreach ($class->getMiddlewares() as $middleware) {
            $middleware->execute($action, ...$this->attributes);
        }

        return $class->$action(...$this->attributes);
    }

    /**
     * Call __invoke magic method
     *
     * ? instead of object type, create an interface for model
     */
    protected function callInvoke(object $class): mixed
    {
        if (!is_callable($class)) {
            throw new \Exception(
                sprintf('Method: "__invoke" does not exist on %s.', $class::class)
            );
        }

        return $class();
    }
}
