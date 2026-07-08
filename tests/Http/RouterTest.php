<?php

declare(strict_types=1);

namespace Tests\Http;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Zerexei\PHPCore\Http\Router;
use Zerexei\PHPCore\Http\Traits\Route;
use Zerexei\PHPCore\Http\Traits\RouterController;

#[CoversClass(Router::class)]
#[CoversClass(Route::class)]
#[CoversClass(RouterController::class)]
class RouterTest extends TestCase
{
    private Router $router;

    protected function setUp(): void
    {
        $this->router = new Router();
    }

    protected function tearDown(): void
    {
        unset($_POST['_method']);
    }

    // -------------------------------------------------------------------------
    // isValidVerb()
    // -------------------------------------------------------------------------

    #[DataProvider('validVerbProvider')]
    public function test_valid_verbs_are_accepted(string $verb): void
    {
        $this->assertTrue($this->router->isValidVerb($verb));
    }

    public static function validVerbProvider(): array
    {
        return [['GET'], ['POST'], ['PUT'], ['PATCH'], ['DELETE']];
    }

    #[DataProvider('invalidVerbProvider')]
    public function test_invalid_verbs_are_rejected(string $verb): void
    {
        $this->assertFalse($this->router->isValidVerb($verb));
    }

    public static function invalidVerbProvider(): array
    {
        return [['OPTIONS'], ['HEAD'], ['CONNECT'], ['TRACE'], ['get'], ['']];
    }

    // -------------------------------------------------------------------------
    // Route registration & dispatch (closures)
    // -------------------------------------------------------------------------

    public function test_get_route_dispatches_closure(): void
    {
        $this->router->get('/hello', fn () => 'Hello');
        $this->assertSame('Hello', $this->router->resolve('hello', 'GET'));
    }

    public function test_post_route_dispatches_closure(): void
    {
        $this->router->post('/submit', fn () => 'Submitted');
        $this->assertSame('Submitted', $this->router->resolve('submit', 'POST'));
    }

    public function test_put_route_dispatches_closure(): void
    {
        $this->router->put('/item', fn () => 'Updated');
        $this->assertSame('Updated', $this->router->resolve('item', 'PUT'));
    }

    public function test_patch_route_dispatches_closure(): void
    {
        $this->router->patch('/item', fn () => 'Patched');
        $this->assertSame('Patched', $this->router->resolve('item', 'PATCH'));
    }

    public function test_delete_route_dispatches_closure(): void
    {
        $this->router->delete('/item', fn () => 'Deleted');
        $this->assertSame('Deleted', $this->router->resolve('item', 'DELETE'));
    }

    public function test_method_is_case_insensitive_in_resolve(): void
    {
        $this->router->get('/ping', fn () => 'pong');
        $this->assertSame('pong', $this->router->resolve('ping', 'get'));
    }

    // -------------------------------------------------------------------------
    // 404 and invalid verb exceptions
    // -------------------------------------------------------------------------

    public function test_resolve_throws_for_unregistered_route(): void
    {
        $this->expectException(\Exception::class);
        $this->router->resolve('does-not-exist', 'GET');
    }

    public function test_resolve_throws_for_wrong_method(): void
    {
        $this->router->post('/only-post', fn () => 'ok');
        $this->expectException(\Exception::class);
        $this->router->resolve('only-post', 'GET');
    }

    public function test_resolve_throws_for_invalid_verb(): void
    {
        $this->expectException(\Exception::class);
        $this->router->resolve('/path', 'OPTIONS');
    }

    // -------------------------------------------------------------------------
    // Method spoofing
    // -------------------------------------------------------------------------

    public function test_post_with_delete_field_dispatches_delete_route(): void
    {
        $this->router->delete('/resource', fn () => 'gone');
        $_POST['_method'] = 'DELETE';
        $this->assertSame('gone', $this->router->resolve('resource', 'POST'));
    }

    public function test_post_with_put_field_dispatches_put_route(): void
    {
        $this->router->put('/resource', fn () => 'replaced');
        $_POST['_method'] = 'PUT';
        $this->assertSame('replaced', $this->router->resolve('resource', 'POST'));
    }

    // -------------------------------------------------------------------------
    // Wildcard routes
    // -------------------------------------------------------------------------

    public function test_int_wildcard_passes_captured_segment(): void
    {
        $this->router->get('/users/:int', fn (string $id) => "user:{$id}");
        $this->assertSame('user:42', $this->router->resolve('users/42', 'GET'));
    }

    public function test_str_wildcard_passes_captured_segment(): void
    {
        $this->router->get('/slug/:str', fn (string $s) => "slug:{$s}");
        $this->assertSame('slug:hello_world', $this->router->resolve('slug/hello_world', 'GET'));
    }

    public function test_char_wildcard_passes_captured_segment(): void
    {
        $this->router->get('/type/:char', fn (string $t) => "type:{$t}");
        $this->assertSame('type:abc', $this->router->resolve('type/abc', 'GET'));
    }

    public function test_any_wildcard_passes_entire_remaining_path(): void
    {
        $this->router->get('/files/:any', fn (string $p) => "file:{$p}");
        $this->assertSame('file:docs/readme.md', $this->router->resolve('files/docs/readme.md', 'GET'));
    }

    public function test_int_wildcard_does_not_match_non_digit_segments(): void
    {
        $this->router->get('/users/:int', fn () => 'matched');
        $this->expectException(\Exception::class);
        $this->router->resolve('users/abc', 'GET');
    }

    public function test_char_wildcard_does_not_match_digits(): void
    {
        $this->router->get('/type/:char', fn () => 'matched');
        $this->expectException(\Exception::class);
        $this->router->resolve('type/123', 'GET');
    }

    // -------------------------------------------------------------------------
    // setHost()
    // -------------------------------------------------------------------------

    public function test_set_host_prefixes_all_routes(): void
    {
        $this->router->setHost('api');
        $this->router->get('/users', fn () => 'list');
        $this->assertSame('list', $this->router->resolve('api/users', 'GET'));
    }

    // -------------------------------------------------------------------------
    // Instance isolation (proves static state was removed)
    // -------------------------------------------------------------------------

    public function test_two_router_instances_do_not_share_routes(): void
    {
        $r1 = new Router();
        $r2 = new Router();
        $r1->get('/only-in-r1', fn () => 'found');

        $this->expectException(\Exception::class);
        $r2->resolve('only-in-r1', 'GET');
    }
}
