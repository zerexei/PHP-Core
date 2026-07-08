<?php

declare(strict_types=1);

namespace Tests\Http;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Zeretei\PHPCore\Http\Request;

#[CoversClass(Request::class)]
class RequestTest extends TestCase
{
    protected function tearDown(): void
    {
        $_GET  = [];
        $_POST = [];
        unset(
            $_SERVER['REQUEST_URI'],
            $_SERVER['REQUEST_METHOD'],
            $_SERVER['HTTP_CLIENT_IP'],
            $_SERVER['HTTP_X_FORWARDED_FOR'],
            $_SERVER['REMOTE_ADDR'],
        );
    }

    // -------------------------------------------------------------------------
    // uri()
    // -------------------------------------------------------------------------

    public function test_uri_strips_query_string(): void
    {
        // uri() trims the leading slash before filter+parse_url, so no leading slash
        $_SERVER['REQUEST_URI'] = '/users?page=1&sort=asc';
        $this->assertSame('users', Request::uri());
    }

    public function test_uri_returns_empty_string_for_root(): void
    {
        // trim('/') = '' → parse_url returns ''
        $_SERVER['REQUEST_URI'] = '/';
        $this->assertSame('', Request::uri());
    }

    public function test_uri_returns_path_without_leading_slash(): void
    {
        $_SERVER['REQUEST_URI'] = '/api/v1/users';
        $this->assertSame('api/v1/users', Request::uri());
    }

    public function test_uri_returns_empty_string_when_not_set(): void
    {
        unset($_SERVER['REQUEST_URI']);
        $this->assertSame('', Request::uri());
    }

    // -------------------------------------------------------------------------
    // method()
    // -------------------------------------------------------------------------

    public function test_method_returns_uppercased_verb(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'post';
        $this->assertSame('POST', Request::method());
    }

    public function test_method_defaults_to_get_when_not_set(): void
    {
        unset($_SERVER['REQUEST_METHOD']);
        $this->assertSame('GET', Request::method());
    }

    public function test_method_preserves_already_uppercase(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'DELETE';
        $this->assertSame('DELETE', Request::method());
    }

    // -------------------------------------------------------------------------
    // query()
    // -------------------------------------------------------------------------

    public function test_query_returns_all_when_no_key_given(): void
    {
        $_GET = ['page' => '1', 'sort' => 'name'];
        $this->assertSame(['page' => '1', 'sort' => 'name'], Request::query());
    }

    public function test_query_returns_value_for_known_key(): void
    {
        $_GET = ['q' => 'php'];
        $this->assertSame('php', Request::query('q'));
    }

    public function test_query_returns_null_for_missing_key(): void
    {
        $_GET = [];
        $this->assertNull(Request::query('missing'));
    }

    // -------------------------------------------------------------------------
    // request()
    // -------------------------------------------------------------------------

    public function test_request_reads_from_post(): void
    {
        $_POST = ['name' => 'Jane'];
        $_GET  = [];
        $this->assertSame('Jane', Request::request('name'));
    }

    public function test_request_falls_back_to_get(): void
    {
        $_POST = [];
        $_GET  = ['search' => 'hello'];
        $this->assertSame('hello', Request::request('search'));
    }

    public function test_request_post_takes_priority_over_get(): void
    {
        $_POST = ['key' => 'from_post'];
        $_GET  = ['key' => 'from_get'];
        $this->assertSame('from_post', Request::request('key'));
    }

    public function test_request_returns_null_when_key_absent(): void
    {
        $_POST = [];
        $_GET  = [];
        $this->assertNull(Request::request('absent'));
    }

    // -------------------------------------------------------------------------
    // ip()
    // -------------------------------------------------------------------------

    public function test_ip_returns_remote_addr_as_last_resort(): void
    {
        unset($_SERVER['HTTP_CLIENT_IP'], $_SERVER['HTTP_X_FORWARDED_FOR']);
        $_SERVER['REMOTE_ADDR'] = '192.168.1.100';
        $this->assertSame('192.168.1.100', Request::ip());
    }

    public function test_ip_prefers_client_ip_header(): void
    {
        $_SERVER['HTTP_CLIENT_IP']       = '10.0.0.1';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '10.0.0.2';
        $_SERVER['REMOTE_ADDR']          = '10.0.0.3';
        $this->assertSame('10.0.0.1', Request::ip());
    }

    public function test_ip_takes_first_entry_from_forwarded_for_list(): void
    {
        unset($_SERVER['HTTP_CLIENT_IP']);
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '203.0.113.5, 198.51.100.1, 192.0.2.1';
        $_SERVER['REMOTE_ADDR']          = '10.0.0.1';
        $this->assertSame('203.0.113.5', Request::ip());
    }

    public function test_ip_skips_invalid_header_value_and_falls_through(): void
    {
        $_SERVER['HTTP_CLIENT_IP']       = 'not-valid';
        unset($_SERVER['HTTP_X_FORWARDED_FOR']);
        $_SERVER['REMOTE_ADDR']          = '172.16.0.1';
        $this->assertSame('172.16.0.1', Request::ip());
    }

    public function test_ip_returns_localhost_fallback_when_all_absent(): void
    {
        unset(
            $_SERVER['HTTP_CLIENT_IP'],
            $_SERVER['HTTP_X_FORWARDED_FOR'],
            $_SERVER['REMOTE_ADDR'],
        );
        $this->assertSame('127.0.0.1', Request::ip());
    }

    public function test_ip_accepts_ipv6_address(): void
    {
        unset($_SERVER['HTTP_CLIENT_IP'], $_SERVER['HTTP_X_FORWARDED_FOR']);
        $_SERVER['REMOTE_ADDR'] = '::1';
        $this->assertSame('::1', Request::ip());
    }
}
