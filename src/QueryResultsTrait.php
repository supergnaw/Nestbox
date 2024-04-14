<?php

declare(strict_types=1);

namespace Supergnaw\Nestbox;

trait QueryResultsTrait
{
    /**
     * Return resulting rows from a query; optionally, return only the first
     * row of data when only one row is expected
     *
     * @param bool $firstResultOnly
     * @return array
     */
    public function results(bool $firstResultOnly = false): array
    {
        return ($firstResultOnly) ? $this->stmt->fetchAll()[0] ?? [] : $this->stmt->fetchAll();
    }

    /**
     * Return the row count from the most recent query
     * @return int
     */
    public function row_count(): int
    {
        return $this->stmt->rowCount();
    }

    /**
     * Get the row ID of the last insert of the active connection
     *
     * @return string
     */
    public function last_insert_id(): string
    {
        return $this->pdo->lastInsertId();
    }

    /**
     * Return resulting rows as key pairs
     *
     * @return array
     */
    public function key_pair(): array
    {
        return $this->stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }
}