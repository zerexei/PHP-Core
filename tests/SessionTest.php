<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Zeretei\PHPCore\Session;

#[CoversClass(Session::class)]
class SessionTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
    }

    // -------------------------------------------------------------------------
    // General session (set / get / all)
    // -------------------------------------------------------------------------

    public function test_set_and_get_integer_value(): void
    {
        $s = new Session();
        $s->set('user_id', 42);
        $this->assertSame(42, $s->get('user_id'));
    }

    public function test_set_and_get_array_value(): void
    {
        $s = new Session();
        $s->set('roles', ['admin', 'editor']);
        $this->assertSame(['admin', 'editor'], $s->get('roles'));
    }

    public function test_get_returns_null_for_missing_key(): void
    {
        $s = new Session();
        $this->assertNull($s->get('does_not_exist'));
    }

    public function test_set_sanitizes_string_values(): void
    {
        $s = new Session();
        $s->set('name', '<script>alert(1)</script>');
        $this->assertStringNotContainsString('<script>', (string) $s->get('name'));
    }

    public function test_all_includes_keys_set_via_set(): void
    {
        $s = new Session();
        $s->set('a', 1);
        $s->set('b', 2);
        $this->assertArrayHasKey('a', $s->all());
        $this->assertArrayHasKey('b', $s->all());
    }

    // -------------------------------------------------------------------------
    // Flash bag
    // -------------------------------------------------------------------------

    public function test_set_flash_and_get_flash(): void
    {
        $s = new Session();
        $s->setFlash('success', 'Saved!');
        $this->assertSame('Saved!', $s->getFlash('success'));
    }

    public function test_get_flash_returns_null_for_missing_key(): void
    {
        $s = new Session();
        $this->assertNull($s->getFlash('missing'));
    }

    public function test_flash_bag_returns_flat_key_value_map(): void
    {
        $s = new Session();
        $s->setFlash('info', 'Hello');
        $s->setFlash('warning', 'Watch out');
        $this->assertSame(['info' => 'Hello', 'warning' => 'Watch out'], $s->flashBag());
    }

    public function test_flash_bag_returns_empty_when_none_set(): void
    {
        $s = new Session();
        $this->assertSame([], $s->flashBag());
    }

    // -------------------------------------------------------------------------
    // Error bag
    // -------------------------------------------------------------------------

    public function test_set_error_flash_and_get_error_flash(): void
    {
        $s = new Session();
        $s->setErrorFlash('email', 'Invalid email address.');
        $this->assertSame('Invalid email address.', $s->getErrorFlash('email'));
    }

    public function test_get_error_flash_returns_null_for_missing_key(): void
    {
        $s = new Session();
        $this->assertNull($s->getErrorFlash('password'));
    }

    public function test_error_bag_returns_flat_key_value_map(): void
    {
        $s = new Session();
        $s->setErrorFlash('name', 'Required.');
        $s->setErrorFlash('email', 'Invalid.');
        $this->assertSame(['name' => 'Required.', 'email' => 'Invalid.'], $s->errorBag());
    }

    public function test_error_bag_returns_empty_when_none_set(): void
    {
        $s = new Session();
        $this->assertSame([], $s->errorBag());
    }

    // -------------------------------------------------------------------------
    // Two-pass flash flush lifecycle
    // -------------------------------------------------------------------------

    public function test_flash_is_readable_in_the_same_request_it_was_set(): void
    {
        $s = new Session();
        $s->setFlash('msg', 'Hi');
        $this->assertSame('Hi', $s->getFlash('msg'));
    }

    public function test_flash_survives_into_the_next_request(): void
    {
        $s1 = new Session();
        $s1->setFlash('msg', 'Hello');
        unset($s1); // flush: remove=false → entry kept

        $s2 = new Session();
        $this->assertSame('Hello', $s2->getFlash('msg'));
        unset($s2); // flush: remove=true → entry deleted
    }

    public function test_flash_is_gone_after_second_request_destructs(): void
    {
        $s1 = new Session();
        $s1->setFlash('msg', 'Hello');
        unset($s1);

        $s2 = new Session();
        $this->assertSame('Hello', $s2->getFlash('msg'));
        unset($s2);

        $s3 = new Session();
        $this->assertNull($s3->getFlash('msg'));
    }

    public function test_flash_set_during_request_is_not_deleted_by_same_request_flush(): void
    {
        // Simulate a leftover entry already marked for removal
        $_SESSION['flash_bag']['old'] = ['value' => 'Old', 'remove' => true];

        $s = new Session();           // markForFlush: 'old' stays remove=true
        $s->setFlash('new', 'Fresh'); // new entry: remove=false
        unset($s);                    // flush: 'old' deleted, 'new' kept

        $this->assertArrayNotHasKey('old', $_SESSION['flash_bag'] ?? []);
        $this->assertSame('Fresh', $_SESSION['flash_bag']['new']['value'] ?? null);
    }
}
