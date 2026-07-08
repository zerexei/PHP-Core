<?php

namespace Zeretei\PHPCore\Database;

/**
 * Thin PDO wrapper providing parameterized query helpers.
 *
 * ## Configuration array keys
 *
 * Preferred (explicit DSN):
 *   - `dsn`      Full PDO DSN string, e.g. "mysql:host=localhost;dbname=myapp;charset=utf8mb4"
 *   - `username` Database username
 *   - `password` Database password
 *   - `options`  Optional array of PDO::ATTR_* constants
 *
 * Legacy (constructed DSN):
 *   - `connection` DSN prefix, e.g. "mysql:host=localhost"
 *   - `name`       Database name (appended as ";dbname=<name>")
 *   - `username`   Database username
 *   - `password`   Database password
 *   - `options`    Optional array of PDO::ATTR_* constants
 */
class QueryBuilder
{
    /**
     * The underlying PDO connection.
     */
    protected \PDO $pdo;

    /**
     * Establish the database connection.
     *
     * @param array<string, mixed> $config See class docblock for accepted keys.
     * @throws \PDOException on connection failure.
     */
    public function __construct(array $config)
    {
        $dsn = $config['dsn']
            ?? ($config['connection'] . ';dbname=' . $config['name']);

        $defaultOptions = [
            \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
        ];

        $options = ($config['options'] ?? []) + $defaultOptions;

        $this->pdo = new \PDO(
            $dsn,
            $config['username'],
            $config['password'],
            $options
        );
    }

    /**
     * Execute a parameterized statement (INSERT / UPDATE / DELETE / DDL).
     * Returns true on success.
     *
     * @param list<mixed> $params
     */
    public function query(string $sql, array $params = []): bool
    {
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Fetch a single row. Returns false when no row matches.
     *
     * @param list<mixed>           $params
     * @return array<string, mixed>|false
     */
    public function fetch(string $sql, array $params = []): array|false
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        return $result === false ? false : (array) $result;
    }

    /**
     * Fetch all matching rows.
     *
     * @param  list<mixed>                $params
     * @return list<array<string, mixed>>
     */
    public function fetchAll(string $sql, array $params = []): array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Return the number of rows matched by the query.
     *
     * @param list<mixed> $params
     */
    public function count(string $sql, array $params = []): int
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    /**
     * Execute a raw SQL statement (e.g., DDL like CREATE TABLE).
     * Returns the number of affected rows, or false on failure.
     */
    public function execute(string $sql): int|false
    {
        return $this->pdo->exec($sql);
    }
}
