<?php

declare(strict_types=1);

namespace Supergnaw\Nestbox;

use PDO;
use PDOStatement;
use Supergnaw\Nestbox\Exception\CannotBindArrayException;
use Supergnaw\Nestbox\Exception\EmptyParamsException;
use Supergnaw\Nestbox\Exception\EmptyQueryException;
use Supergnaw\Nestbox\Exception\FailedToBindValueException;
use Supergnaw\Nestbox\Exception\InvalidTableException;
use Supergnaw\Nestbox\Exception\QueryErrorException;

trait QueryExecutionTrait
{
    protected PDOStatement $stmt;
    /**
     * Executes a query against the database
     *
     * @param string $query
     * @param array $params
     * @param bool $close
     * @param bool $retry
     * @return bool
     */
    public function query_execute(string $query, array $params = [], bool $close = false, bool $retry = true): bool
    {
        // check query emptiness
        if (empty(trim($query))) {
            if ($close) {
                // an empty query is okay if we also are attempting to close the connection
                $this->close();
                return true;
            } else {
                throw new EmptyQueryException("Empty MySQL query provided.");
            }
        }

        // parameter validation
        $params = $this->validate_parameters($query, $params);

        // connect to database
        $this->connect();

        // prepare statement
        $this->prep($query, $params);

        // bind parameters
        if ($params) foreach ($params as $var => $val) $this->bind($var, $val);

        // execute
        try {
            $result = $this->execute();

            if ($close) $this->close();

            return $result;
        } catch (InvalidTableException $tableName) {
            // if it's a class table, try to create it and reattempt query execution
            if (str_starts_with($table, $this::PACKAGE_NAME) && $retry) {
                $this->create_class_tables();
                return $this->query_execute($query, $params, $close, retry: false);
            }

            throw new InvalidTableException($tableName);
        }
    }

    protected function sanitize_paramaters(array $params): array
    {
        $output = [];

        foreach ($params as $col => $val) $output[$this->valid_schema_string($col)] = $val;

        return $output;
    }

    /**
     * Searches for paramater :names in a query and removes unused column => value pairs
     *
     * @param string $query
     * @param array $params
     * @return array
     */
    protected function verify_parameters(string $query, array $params): array
    {
        foreach ($params as $var => $val) {
            // remove unwanted parameters
            if (!strpos($query, ":{$var}")) {
                unset($params[$var]);
            }
        }
        return $params;
    }

    protected function validate_parameters(string $query, array $params): array
    {
        $output = [];

        foreach ($params as $key => $value) {
            $key = $this->valid_schema_string($key);
            if (strpos($query, ":$key")) $output[$key] = $value;
        }

        return $output;
    }

    public static function confirm_nonempty_params(array $params): bool
    {
        $empty = [];

        foreach ($params as $param => $value) {
            if (isset($param)) if (!empty(trim("{$value}"))) continue;
            $empty[] = $param;
        }

        if (0 < count($empty)) {
            $keys = implode(", ", $empty);
            throw new EmptyParamsException("Multiple missing or empty parameters: $keys");
        }

        return true;
    }

    private function last_id(): int|string|null
    {
        return null;
    }

    protected function compile_paramaterized_fields(array $params, string $conjunction = "AND", bool $useComma = false): string
    {
        $output = [];

        foreach ($params as $key => $value) $output[] = "`$key` = :$key";

        if ($useComma) {
            $sep = ",";
        } else {
            $sep = (
            preg_match("/^(and|or)$/i", trim($conjunction), $con)
            ) ? strtoupper($con[0]) : "AND";
        }

        return ($output) ? implode(" $sep ", $output) : "";
    }

    /**
     * Prepare a query into a statement
     *
     * @param string $query
     * @param array|null $params
     * @return bool
     */
    protected function prep(string $query, array $params = []): bool
    {
        if (empty($params)) {
            // prepare a statement
            $this->stmt = $this->pdo->prepare($query);
        } else {
            // prepare a statement with parameters
            $this->stmt = $this->pdo->prepare($query, $params);
        }
        return true;
    }

    /**
     * Bind a variable to a paramters
     *
     * @param $variable
     * @param $value
     * @return bool
     */
    protected function bind($variable, $value): bool
    {
        // set binding type
        $type = Nestbox::check_variable_type($value);
        if ("array" == $type) {
            throw new CannotBindArrayException("Cannot bind array type to :{$variable}");
        }

        // backwards compatibility or whatever
        $variable = (!str_starts_with($variable, ':')) ? ":{$variable}" : $variable;

        // bind value to parameter
        if (true === $this->stmt->bindValue($variable, $value, $type)) {
            return true;
        }

        // we didn't do it
        throw new FailedToBindValueException("Failed to bind '{$value}' to :{$variable} ({$type})");
    }

    /**
     * Checks variable type for parameter binding
     *
     * @param $var
     * @return int|string
     */
    protected static function check_variable_type($var): int|string
    {
        if (is_int($var)) return PDO::PARAM_INT;
        if (is_bool($var)) return PDO::PARAM_BOOL;
        if (is_null($var)) return PDO::PARAM_NULL;
        if (is_array($var)) return "array";
        return PDO::PARAM_STR;
    }

    /**
     * Execute a statement
     *
     * @return bool
     */
    protected function execute(): bool
    {
        // execute query
        try {
            if (!$this->stmt->execute()) {
                $error = $this->stmt->errorInfo();
                throw new QueryErrorException("MySQL error {$error[1]}: {$error[2]} ({$error[0]})");
            }
        } catch (PDOException $e) {
            $msg = $e->getMessage();
            throw new PDOException("PDO Exception: {$msg}");
        }
        return true;
    }
}