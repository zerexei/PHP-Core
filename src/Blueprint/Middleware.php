<?php

namespace Zeretei\PHPCore\Blueprint;

abstract class Middleware
{
    /**
     * Router controller current action
     */
    protected array $actions = [];

    public function __construct(array $actions = [])
    {
        $this->actions = $actions;
    }

    /**
     * Return all actions
     */
    public function getActions(): array
    {
        return $this->actions;
    }

    /**
     * Determine if the middleware applies to the given action
     */
    public function shouldExecute(string $action): bool
    {
        if (empty($this->actions)) {
            return true;
        }

        return in_array($action, $this->actions);
    }

    /**
     * Execute middleware
     */
    abstract public function execute(string $action);
}
