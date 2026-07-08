<?php

namespace Zerexei\PHPCore\Blueprint;

abstract class Middleware
{
    /**
     * Router controller actions this middleware applies to.
     * An empty array means the middleware applies to all actions.
     */
    protected array $actions = [];

    public function __construct(array $actions = [])
    {
        $this->actions = $actions;
    }

    /**
     * Return the actions this middleware is scoped to.
     */
    public function getActions(): array
    {
        return $this->actions;
    }

    /**
     * Determine if this middleware should run for the given action.
     * Returns true when $actions is empty (applies to all) or the action is listed.
     */
    public function shouldExecute(string $action): bool
    {
        if (empty($this->actions)) {
            return true;
        }

        return in_array($action, $this->actions, true);
    }

    /**
     * Execute the middleware logic.
     *
     * @param string $action    The controller action being dispatched.
     * @param mixed  ...$params Any wildcard route parameters for the current route.
     */
    abstract public function execute(string $action, mixed ...$params): void;
}
