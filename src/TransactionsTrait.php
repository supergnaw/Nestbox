<?php

declare(strict_types=1);

namespace Supergnaw\Nestbox;

use Supergnaw\Nestbox\Exception\TransactionBeginFailedException;
use Supergnaw\Nestbox\Exception\TransactionCommitFailedException;
use Supergnaw\Nestbox\Exception\TransactionException;
use Supergnaw\Nestbox\Exception\TransactionInProgressException;
use Supergnaw\Nestbox\Exception\TransactionRollbackFailedException;

trait TransactionsTrait
{
    /**
     * Use a single query to perform an incremental transaction
     *
     * @param string $query
     * @param array $params
     * @param bool $commit
     * @param bool $close
     * @return mixed
     */
    public function transaction(string $query, array $params, bool $commit = false, bool $close = false): mixed
    {
        try {
            // start transaction if not already in progress
            if (!$this->pdo->inTransaction()) {
                $this->pdo->beginTransaction();
                if (!$this->pdo->inTransaction()) {
                    // couldn't start a transaction
                    throw new TransactionBeginFailedException("Failed to begin new transaction.");
                }
            }

            // perform single query for the transaction
            if ($this->execute($query, $params)) {
                $results = [
                    'rows' => $this->results(),
                    'row_count' => $this->row_count(),
                    'last_id' => $this->last_id(),
                ];
            }

            // commit the transaction and return any results
            if (true === $commit) {
                if ($this->pdo->commit()) {
                    // commit the transaction and return the results
                    return $results;
                } else {
                    throw new TransactionCommitFailedException("Failed to commit transaction.");
                }
            } else {
                // return the query results but leave transaction in progress
                return $results;
            }
        } catch (\Exception $e) {
            // oh no! roll back database and re-throw whatever fun error was encountered
            if (!$this->rollback()) {
                // we're really not having a good day today are we...
                throw new TransactionRollbackFailedException($e->getMessage() . " -- AND -- Failed to rollback database transaction.");
            }
            throw new TransactionException($e->getMessage());
        }
    }

    /**
     * Pass an array of SQL queries and perform a transaction with them
     *
     * @param array $queries
     * @return array
     */
    public function transaction_execute(array $queries): array
    {
        try {
            // connect to database
            $this->connect();

            // start transaction if not already in progress
            if ($this->pdo->inTransaction()) {
                throw new TransactionInProgressException("Unable to start new transaction while one is already in progress.");
            }
            $this->pdo->beginTransaction();

            // perform transaction
            $results = [];
            foreach ($queries as $query => $params) {
                // prepare query
                $this->prep($query, $params);

                // bind parameters
                if (!empty($params)) {
                    foreach ($params as $var => $val) {
                        $this->bind($var, $val);
                    }
                }

                if ($this->execute()) {
                    $results[] = [
                        'rows' => $this->results(),
                        'row_count' => $this->row_count(),
                        'last_id' => $this->last_id(),
                    ];
                }
            }

            // commit the transaction and return any results
            if ($this->pdo->commit()) {
                return $results;
            } else {
                throw new TransactionCommitFailedException("Failed to commit transaction.");
            }
        } catch (\Exception $e) {
            // Oh no, we dun goof'd! Roll back database and re-throw the error
            $this->pdo->rollback();
            throw new TransactionException($e->getMessage());
        }
    }

    public function rollback(): bool
    {
        if ($this->pdo->inTransaction()) {
            if ($this->pdo->rollback()) {
                return true;
            }
        }
        return false;
    }
}