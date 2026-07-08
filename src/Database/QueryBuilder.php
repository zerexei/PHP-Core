<?php

namespace Zeretei\PHPCore\Database;

class QueryBuilder
{
    /**
     * PDO instance
     */
    protected \PDO $pdo;

    /**
     * Establish connection
     */
    public function __construct(array $config)
    {
        $defaultOptions = [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
        ];
        $options = ($config['options'] ?? []) + $defaultOptions;

        $this->pdo = new \PDO(
            $config['connection'] . ';dbname=' . $config['name'],
            $config['username'],
            $config['password'],
            $options
        );
    }

    /**
     * Query a SQL statement
     */
    public function query(string $sql, array $params = []): bool
    {
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Fetch a single row from the database
     */
    public function fetch(string $sql, array $params = []): mixed
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
    }

    /**
     * Fetch all the rows from the database
     */
    public function fetchAll(string $sql, array $params = []): array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Count all the rows from the database
     */
    public function count(string $sql, array $params = []): int
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    /**
     * Execute a SQL command
     */
    public function execute(string $sql): int|false
    {
        return $this->pdo->exec($sql);
    }
}
