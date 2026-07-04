<?php

namespace Zeretei\PHPCore\Blueprint;

use Zeretei\PHPCore\Application;

abstract class Model
{
    /**
     * Database table name
     */
    protected string $table;

    /**
     * Fillable inputs
     */
    protected array $fillable = [];

    /**
     * SQL where selector key
     */
    protected string $key = 'id';

    public function __construct()
    {
        if (!isset($this->table)) {
            $this->table = $this->getBaseClassname() . 's';
        }
    }

    /**
     * Execute SQL Insert statement
     */
    public function insert(array $params): bool
    {
        $params = $this->filter($params);

        $columns = implode(',', array_keys($params));
        $values = trim(str_repeat('?,', count($params)), ',');

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $this->table,
            $columns,
            $values
        );

        return Application::get('database')->query($sql, array_values($params));
    }

    /**
     * Execute update SQL statement
     */
    public function update(string|int $id, array $params): bool
    {
        $params = $this->filter($params);

        $keys = array_keys($params);
        $set = trim(implode('=?,', $keys) . '=?', ',');
        $key = [$this->key => $id];
        $params[] = current($key);

        $sql = sprintf(
            'UPDATE %s SET %s WHERE %s = ?',
            $this->table,
            $set,
            key($key)
        );

        return Application::get('database')->query($sql, array_values($params));
    }

    /**
     * Execute delete SQL statement
     */
    public function delete(string|int $id, $key = null): bool
    {
        $sql = sprintf(
            'DELETE FROM %s WHERE %s = ?',
            $this->table,
            $key ?? $this->key
        );

        return Application::get('database')->query($sql, [$id]);
    }

    /**
     * Execute Select SQL statement
     */
    public function select(string|int $id, $key = null): array|object|false
    {
        $sql = sprintf(
            "SELECT * FROM %s WHERE %s = ? LIMIT 1",
            $this->table,
            $key ?? $this->key
        );

        return Application::get('database')->fetch($sql, [$id]);
    }

    /**
     * Execute select all SQL statement
     */
    public function all(): array
    {
        $sql = "SELECT * FROM {$this->table}";
        return Application::get('database')->fetchAll($sql);
    }

    /**
     * Filter $request with $this->fillable
     */
    protected function filter(array $params): array
    {
        if (empty($this->fillable)) {
            throw new \Exception('$fillable must have a value.');
        }

        return array_filter(
            $params,
            fn ($_, $key) => in_array($key, $this->fillable),
            ARRAY_FILTER_USE_BOTH
        );
    }

    /**
     * Filename  to plural classname
     */
    protected function getBaseClassname(): string
    {
        $class = get_called_class();
        $base = basename(str_replace('\\', DIRECTORY_SEPARATOR, $class));
        return  strtolower($base);
    }
}
