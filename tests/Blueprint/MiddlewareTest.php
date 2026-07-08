<?php

declare(strict_types=1);

namespace Tests\Blueprint;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Zerexei\PHPCore\Blueprint\Middleware;

#[CoversClass(Middleware::class)]
class MiddlewareTest extends TestCase
{
    private function makeMiddleware(array $actions = []): Middleware
    {
        return new class($actions) extends Middleware {
            /** @var list<array{action: string, params: list<mixed>}> */
            public array $calls = [];

            public function execute(string $action, mixed ...$params): void
            {
                $this->calls[] = ['action' => $action, 'params' => $params];
            }
        };
    }

    // -------------------------------------------------------------------------
    // shouldExecute() — global middleware (empty actions list)
    // -------------------------------------------------------------------------

    public function test_empty_actions_list_matches_any_action(): void
    {
        $m = $this->makeMiddleware([]);
        $this->assertTrue($m->shouldExecute('index'));
        $this->assertTrue($m->shouldExecute('store'));
        $this->assertTrue($m->shouldExecute('destroy'));
    }

    // -------------------------------------------------------------------------
    // shouldExecute() — scoped middleware
    // -------------------------------------------------------------------------

    public function test_should_execute_returns_true_for_listed_action(): void
    {
        $m = $this->makeMiddleware(['update', 'destroy']);
        $this->assertTrue($m->shouldExecute('update'));
        $this->assertTrue($m->shouldExecute('destroy'));
    }

    public function test_should_execute_returns_false_for_unlisted_action(): void
    {
        $m = $this->makeMiddleware(['update', 'destroy']);
        $this->assertFalse($m->shouldExecute('index'));
        $this->assertFalse($m->shouldExecute('store'));
        $this->assertFalse($m->shouldExecute('show'));
    }

    // -------------------------------------------------------------------------
    // getActions()
    // -------------------------------------------------------------------------

    public function test_get_actions_returns_the_registered_actions(): void
    {
        $m = $this->makeMiddleware(['delete', 'update']);
        $this->assertSame(['delete', 'update'], $m->getActions());
    }

    public function test_get_actions_returns_empty_array_by_default(): void
    {
        $m = $this->makeMiddleware();
        $this->assertSame([], $m->getActions());
    }

    // -------------------------------------------------------------------------
    // execute() argument forwarding
    // -------------------------------------------------------------------------

    public function test_execute_receives_action_name(): void
    {
        $m = $this->makeMiddleware();
        $m->execute('update');
        $this->assertCount(1, $m->calls);
        $this->assertSame('update', $m->calls[0]['action']);
    }

    public function test_execute_receives_variadic_route_params(): void
    {
        $m = $this->makeMiddleware();
        $m->execute('show', '42', 'extra');
        $this->assertSame(['42', 'extra'], $m->calls[0]['params']);
    }
}
