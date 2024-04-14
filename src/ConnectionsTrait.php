<?php

declare(strict_types=1);

namespace Supergnaw\Nestbox;

use PDO;
use PDOException;
use Supergnaw\Nestbox\Exception\NestboxException;

trait ConnectionsTrait
{
    protected PDO $pdo;
    /**
     * Connect to the database, returns true on success, false on fail
     *
     * @return bool
     */
    protected function connect(): bool
    {
        // check for existing connection
        if ($this->check_connection()) return true;

        // MySQL Database
        try {
            $this->pdo = new PDO(
                "mysql:host={$this->host};dbname={$this->name}",
                $this->user,
                $this->pass,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_PERSISTENT => true,
                    PDO::ATTR_EMULATE_PREPARES => true, // off for :named placeholders
                    PDO::ATTR_STRINGIFY_FETCHES => false,
                ]
            );
        } catch (PDOException $e) {
            throw new NestboxException($e->getMessage());
        }

        // successful connection
        return true;
    }

    /**
     * Check if a connection exists
     *
     * @return bool
     */
    protected function check_connection(): bool
    {
        if (!empty($this->pdo)) {
            // test existing connection for timeout
            $this->prep("SELECT 1");
            $this->execute();
            $rows = $this->results();

            // check test results
            if (1 === $rows[0]['1']) return true;

            // kill dead connection
            $this->close();
        }

        return false;
    }

    /**
     * Close an existing connection
     */
    protected function close(): void
    {
        // https://www.php.net/manual/en/pdo.connections.php
        // "To close the connection, you need to destroy the object"
        unset($this->pdo);
    }
}