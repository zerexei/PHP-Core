<?php

declare(strict_types=1);

namespace Tests\Http;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Zerexei\PHPCore\Http\Response;

#[CoversClass(Response::class)]
class ResponseTest extends TestCase
{
    // -------------------------------------------------------------------------
    // isValidCode()
    // -------------------------------------------------------------------------

    public function test_accepts_lower_boundary_100(): void
    {
        $this->assertTrue(Response::isValidCode(100));
    }

    public function test_accepts_upper_boundary_599(): void
    {
        $this->assertTrue(Response::isValidCode(599));
    }

    public function test_rejects_below_boundary_99(): void
    {
        $this->assertFalse(Response::isValidCode(99));
    }

    public function test_rejects_above_boundary_600(): void
    {
        $this->assertFalse(Response::isValidCode(600));
    }

    #[DataProvider('commonStatusCodesProvider')]
    public function test_accepts_all_common_status_codes(int $code): void
    {
        $this->assertTrue(Response::isValidCode($code));
    }

    public static function commonStatusCodesProvider(): array
    {
        return [
            [200], [201], [204],
            [301], [302], [307],
            [400], [401], [403], [404], [405], [408], [429],
            [500], [502], [503],
        ];
    }

    // -------------------------------------------------------------------------
    // setStatusCode()
    // -------------------------------------------------------------------------

    public function test_set_status_code_throws_for_code_below_100(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/99/');
        Response::setStatusCode(99);
    }

    public function test_set_status_code_throws_for_code_above_599(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/600/');
        Response::setStatusCode(600);
    }

    public function test_set_status_code_does_not_throw_for_valid_code(): void
    {
        $this->expectNotToPerformAssertions();
        Response::setStatusCode(200);
    }

    // -------------------------------------------------------------------------
    // HTTP constants
    // -------------------------------------------------------------------------

    public function test_http_constants_have_correct_values(): void
    {
        $this->assertSame(200, Response::HTTP_OK);
        $this->assertSame(201, Response::HTTP_CREATED);
        $this->assertSame(204, Response::HTTP_NO_CONTENT);
        $this->assertSame(301, Response::HTTP_MOVED_PERMANENTLY);
        $this->assertSame(302, Response::HTTP_FOUND);
        $this->assertSame(307, Response::HTTP_TEMPORARY_REDIRECT);
        $this->assertSame(400, Response::HTTP_BAD_REQUEST);
        $this->assertSame(401, Response::HTTP_UNAUTHORIZED);
        $this->assertSame(403, Response::HTTP_FORBIDDEN);
        $this->assertSame(404, Response::HTTP_NOT_FOUND);
        $this->assertSame(500, Response::HTTP_INTERNAL_SERVER_ERROR);
        $this->assertSame(503, Response::HTTP_SERVICE_UNAVAILABLE);
    }
}
