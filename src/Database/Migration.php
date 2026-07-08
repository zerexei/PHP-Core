<?php

namespace Zerexei\PHPCore\Database;

use \Zerexei\PHPCore\Application;

/**
 * File-based migration runner.
 *
 * ## Migration file naming convention
 *
 * Files must live in `<path.databases>/migrations/` and follow this pattern:
 *
 *   `<numeric_prefix>_<PascalCase_parts>.php`
 *
 * Examples:
 *   - `0001_create_users_table.php`  → class `CreateUsersTable`
 *   - `0002_add_email_to_users.php`  → class `AddEmailToUsers`
 *
 * The numeric prefix determines the application order (scandir alphabetical).
 * Each file must declare a class whose name matches the derived class name above,
 * and that class must implement an `up(): string` method returning a valid SQL statement.
 *
 * Applied migration filenames are recorded in the `migrations` database table so
 * they are never run twice.
 */
class Migration
{
    /**
     * Apply all pending migrations in alphabetical filename order.
     *
     * @return list<string> Filenames of the newly applied migrations.
     * @throws \Exception on configuration or file/class errors.
     */
    public function apply(): array
    {
        if (!Application::has('database')) {
            throw new \Exception('A database service must be registered before running migrations.');
        }

        if (!Application::has('path.databases')) {
            throw new \Exception('The "path.databases" container binding must be set before running migrations.');
        }

        $migrationDir = Application::get('path.databases') . '/migrations';

        if (!is_dir($migrationDir)) {
            throw new \Exception(
                sprintf('Migration directory "%s" does not exist.', $migrationDir)
            );
        }

        $this->createMigrationsTable();

        $applied  = $this->getAppliedMigrations();
        $files    = array_diff((array) scandir($migrationDir), ['.', '..']);
        $pending  = array_diff($files, $applied);

        $newlyApplied = [];

        foreach ($pending as $filename) {
            require_once $migrationDir . '/' . $filename;

            $class = $this->deriveClassname($filename);

            if (!class_exists($class)) {
                throw new \Exception(
                    sprintf('Expected class "%s" not found in migration file "%s".', $class, $filename)
                );
            }

            $migration = new $class();

            if (!method_exists($migration, 'up')) {
                throw new \Exception(
                    sprintf('Migration class "%s" must implement an up(): string method.', $class)
                );
            }

            $sql = $migration->up();

            if (!is_string($sql)) {
                throw new \Exception(
                    sprintf('Migration "%s::up()" must return a SQL string.', $class)
                );
            }

            Application::get('database')->execute($sql);

            $newlyApplied[] = $filename;
        }

        $this->saveMigrations($newlyApplied);

        return $newlyApplied;
    }

    /**
     * Create the migrations tracking table if it does not already exist.
     */
    protected function createMigrationsTable(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS migrations (
            id         INT AUTO_INCREMENT PRIMARY KEY,
            migration  VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";

        Application::get('database')->execute($sql);
    }

    /**
     * Return the filenames of all previously applied migrations.
     *
     * @return list<string>
     */
    protected function getAppliedMigrations(): array
    {
        $rows = Application::get('database')->fetchAll('SELECT migration FROM migrations');
        return array_map(fn (array $row) => $row['migration'], $rows);
    }

    /**
     * Persist the newly applied migration filenames to the tracking table.
     */
    protected function saveMigrations(array $migrations): bool
    {
        if (empty($migrations)) {
            return false;
        }

        $placeholders = implode(', ', array_fill(0, count($migrations), '(?)'));
        $sql = "INSERT INTO migrations (migration) VALUES {$placeholders}";

        return Application::get('database')->query($sql, $migrations);
    }

    /**
     * Derive the expected PHP class name from a migration filename.
     *
     * The numeric prefix (e.g. "0001_") is stripped, the remaining underscore-
     * delimited words are each ucfirst'd and joined.
     *
     * Examples:
     *   "0001_create_users_table.php" → "CreateUsersTable"
     *   "0002_add_email_to_users.php" → "AddEmailToUsers"
     */
    protected function deriveClassname(string $filename): string
    {
        $name  = pathinfo($filename, PATHINFO_FILENAME);
        $parts = explode('_', $name);

        // Drop the leading numeric prefix if present.
        if (count($parts) > 1 && ctype_digit($parts[0])) {
            $parts = array_slice($parts, 1);
        }

        return implode('', array_map('ucfirst', $parts));
    }
}
