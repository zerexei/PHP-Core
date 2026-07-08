<?php

declare(strict_types=1);

namespace Tests\Database;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Zerexei\PHPCore\Database\Migration;

#[CoversClass(Migration::class)]
class MigrationTest extends TestCase
{
    private Migration $migration;

    protected function setUp(): void
    {
        $this->migration = new class extends Migration {
            public function expose(string $filename): string
            {
                return $this->deriveClassname($filename);
            }
        };
    }

    // -------------------------------------------------------------------------
    // deriveClassname()
    // -------------------------------------------------------------------------

    #[DataProvider('filenameToClassProvider')]
    public function test_derive_classname(string $filename, string $expected): void
    {
        $this->assertSame($expected, $this->migration->expose($filename));
    }

    public static function filenameToClassProvider(): array
    {
        return [
            'standard with 4-digit prefix'  => ['0001_create_users_table.php',   'CreateUsersTable'],
            'standard with single word'      => ['0002_users.php',                'Users'],
            'multi-segment name'             => ['0003_add_email_to_users.php',   'AddEmailToUsers'],
            'alter table migration'          => ['0004_alter_users_add_role.php', 'AlterUsersAddRole'],
            'no numeric prefix'             => ['create_posts_table.php',         'CreatePostsTable'],
            'single word no prefix'         => ['users.php',                      'Users'],
            'high sequence number'          => ['9999_drop_old_table.php',        'DropOldTable'],
        ];
    }

    public function test_numeric_prefix_is_not_present_in_result(): void
    {
        $result = $this->migration->expose('0001_create_users_table.php');
        $this->assertStringStartsNotWith('0', $result);
    }

    public function test_result_starts_with_uppercase_letter(): void
    {
        $result = $this->migration->expose('0001_create_users_table.php');
        $this->assertMatchesRegularExpression('/^[A-Z]/', $result);
    }

    public function test_result_contains_no_underscores(): void
    {
        $result = $this->migration->expose('0001_create_users_table.php');
        $this->assertStringNotContainsString('_', $result);
    }

    public function test_non_digit_first_segment_is_not_stripped(): void
    {
        // "create" is not all digits, so nothing should be stripped
        $this->assertSame('CreatePostsTable', $this->migration->expose('create_posts_table.php'));
    }
}
