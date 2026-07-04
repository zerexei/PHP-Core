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
     * Execute middleware
     */
    abstract public function execute(string $action);
}
