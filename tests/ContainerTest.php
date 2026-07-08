<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Zerexei\PHPCore\Container;

#[CoversClass(Container::class)]
class ContainerTest extends TestCase
{
    protected function setUp(): void
    {
        $this->resetStaticState();
    }

    protected function tearDown(): void
    {
        $this->resetStaticState();
    }

    private function resetStaticState(): void
    {
        $ref = new \ReflectionClass(Container::class);

        $registry = $ref->getProperty('registry');
        $registry->setValue(null, []);

        $instance = $ref->getProperty('instance');
        $instance->setValue(null, null);
    }

    public function test_bind_stores_and_get_retrieves_scalar(): void
    {
        Container::bind('greeting', 'hello');
        $this->assertSame('hello', Container::get('greeting'));
    }

    public function test_bind_stores_and_get_retrieves_object(): void
    {
        $obj = new \stdClass();
        $obj->value = 42;
        Container::bind('thing', $obj);
        $this->assertSame($obj, Container::get('thing'));
    }

    public function test_bind_overwrites_existing_binding(): void
    {
        Container::bind('key', 'first');
        Container::bind('key', 'second');
        $this->assertSame('second', Container::get('key'));
    }

    public function test_get_throws_for_unregistered_key(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/"missing"/');
        Container::get('missing');
    }

    public function test_has_returns_true_when_key_is_bound(): void
    {
        Container::bind('x', 1);
        $this->assertTrue(Container::has('x'));
    }

    public function test_has_returns_false_when_key_is_not_bound(): void
    {
        $this->assertFalse(Container::has('not_here'));
    }

    public function test_all_returns_empty_array_when_nothing_bound(): void
    {
        $this->assertSame([], Container::all());
    }

    public function test_all_returns_all_bound_services(): void
    {
        Container::bind('a', 1);
        Container::bind('b', 2);
        $this->assertSame(['a' => 1, 'b' => 2], Container::all());
    }

    public function test_get_instance_returns_a_container(): void
    {
        $this->assertInstanceOf(Container::class, Container::getInstance());
    }

    public function test_get_instance_returns_the_same_instance_each_time(): void
    {
        $a = Container::getInstance();
        $b = Container::getInstance();
        $this->assertSame($a, $b);
    }
}
