<?php

namespace Zeretei\PHPCore\Blueprint;

use Zeretei\PHPCore\Application;

/**
 * Base class for database-backed models.
 *
 * Subclasses must define $fillable. $table defaults to the pluralised lowercase
 * class name (e.g. class User → table "users").
 */
abstract class Model
{
    /**
     * The database table this model reads from and writes to.
     */
    protected string $table;

    /**
     * Column names that may be written by insert() and update().
     * Any key not in this list is silently dropped.
     *
     * @var list<string>
     */
    protected array $fillable = [];

    /**
     * The primary key column used in WHERE clauses.
     */
    protected string $key = 'id';

    public function __construct()
    {
        if (!isset($this->table)) {
            $this->table = $this->getBaseClassname() . 's';
        }
    }

    /**
     * Insert a new row.
     * Only keys present in $fillable are written.
     *
     * @param array<string, mixed> $params
     * @throws \Exception when $fillable is empty.
     */
    public function insert(array $params): bool
    {
        $params  = $this->filter($params);
        $columns = implode(', ', array_keys($params));
        $placeholders = implode(', ', array_fill(0, count($params), '?'));

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $this->table,
            $columns,
            $placeholders
        );

        return Application::get('database')->query($sql, array_values($params));
    }

    /**
     * Update an existing row identified by its primary key (or a custom key column).
     * Only keys present in $fillable are written.
     *
     * @param string|int           $id     Value of the key column to match.
     * @param array<string, mixed> $params Columns to update.
     * @throws \Exception when $fillable is empty.
     */
    public function update(string|int $id, array $params, ?string $key = null): bool
    {
        $params    = $this->filter($params);
        $keyColumn = $key ?? $this->key;
        $set       = implode(' = ?, ', array_keys($params)) . ' = ?';

        $sql = sprintf(
            'UPDATE %s SET %s WHERE %s = ?',
            $this->table,
            $set,
            $keyColumn
        );

        return Application::get('database')->query($sql, [...array_values($params), $id]);
    }

    /**
     * Delete a row by its primary key (or a custom key column).
     *
     * @param string|int $id Value of the key column to match.
     */
    public function delete(string|int $id, ?string $key = null): bool
    {
        $sql = sprintf(
            'DELETE FROM %s WHERE %s = ?',
            $this->table,
            $key ?? $this->key
        );

        return Application::get('database')->query($sql, [$id]);
    }

    /**
     * Fetch a single row by its primary key (or a custom key column).
     *
     * @param string|int $id Value of the key column to match.
     * @return array<string, mixed>|false
     */
    public function select(string|int $id, ?string $key = null): array|false
    {
        $sql = sprintf(
            'SELECT * FROM %s WHERE %s = ? LIMIT 1',
            $this->table,
            $key ?? $this->key
        );

        return Application::get('database')->fetch($sql, [$id]);
    }

    /**
     * Fetch all rows from the table.
     *
     * @return list<array<string, mixed>>
     */
    public function all(): array
    {
        return Application::get('database')->fetchAll("SELECT * FROM {$this->table}");
    }

    /**
     * Filter $params to only the columns declared in $fillable.
     *
     * @param  array<string, mixed> $params
     * @return array<string, mixed>
     * @throws \Exception when $fillable is empty (mass-assignment guard).
     */
    protected function filter(array $params): array
    {
        if (empty($this->fillable)) {
            throw new \Exception(
                sprintf('$fillable is empty on %s. Define the writable columns.', static::class)
            );
        }

        return array_filter(
            $params,
            fn (mixed $_, string $key) => in_array($key, $this->fillable, true),
            ARRAY_FILTER_USE_BOTH
        );
    }

    /**
     * Derive the default table name from the unqualified class name.
     * Example: App\Models\User → "user" → "users".
     */
    protected function getBaseClassname(): string
    {
        $class = get_called_class();
        $pos   = strrpos($class, '\\');
        $base  = $pos === false ? $class : substr($class, $pos + 1);
        return strtolower($base);
    }
}
