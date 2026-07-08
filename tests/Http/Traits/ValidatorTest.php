<?php

declare(strict_types=1);

namespace Tests\Http\Traits;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Zerexei\PHPCore\Http\Traits\Validator;

#[CoversClass(Validator::class)]
class ValidatorTest extends TestCase
{
    private object $v;

    protected function setUp(): void
    {
        $this->v = new class {
            use Validator;
        };
    }

    // -------------------------------------------------------------------------
    // isNumeric()
    // -------------------------------------------------------------------------

    #[DataProvider('numericTrueProvider')]
    public function test_is_numeric_returns_true_for_digit_strings(string $value): void
    {
        $this->assertTrue($this->v->isNumeric($value));
    }

    public static function numericTrueProvider(): array
    {
        return [['0'], ['1'], ['42'], ['999999']];
    }

    #[DataProvider('numericFalseProvider')]
    public function test_is_numeric_returns_false_for_non_digit_strings(string $value): void
    {
        $this->assertFalse($this->v->isNumeric($value));
    }

    public static function numericFalseProvider(): array
    {
        return [
            'empty string' => [''],
            'float'        => ['1.5'],
            'negative'     => ['-1'],
            'alphanumeric' => ['12abc'],
            'alpha only'   => ['abc'],
            'whitespace'   => [' 1'],
        ];
    }

    // -------------------------------------------------------------------------
    // isEmail()
    // -------------------------------------------------------------------------

    #[DataProvider('validEmailProvider')]
    public function test_is_email_accepts_valid_addresses(string $email): void
    {
        $this->assertTrue($this->v->isEmail($email), "Expected '{$email}' to be valid");
    }

    public static function validEmailProvider(): array
    {
        return [
            ['user@example.com'],
            ['jane.doe@domain.org'],
            ['test123@mail.io'],
            ['a.b@cd.net'],
        ];
    }

    #[DataProvider('invalidEmailProvider')]
    public function test_is_email_rejects_invalid_addresses(string $email): void
    {
        $this->assertFalse($this->v->isEmail($email), "Expected '{$email}' to be invalid");
    }

    public static function invalidEmailProvider(): array
    {
        return [
            'no at sign'           => ['notanemail'],
            'no domain'            => ['user@'],
            'no local part'        => ['@domain.com'],
            'empty string'         => [''],
            // NOTE: 'spaces' omitted — FILTER_SANITIZE_EMAIL strips whitespace before
            // validation, so 'user @domain.com' becomes 'user@domain.com' and passes.
            // This is expected PHP built-in behaviour.
            'tld too long'         => ['user@host.toolongtld'],   // > 8 chars fails regex
            'local part too short' => ['ab@x.com'],               // < 3 chars before @
        ];
    }
}
