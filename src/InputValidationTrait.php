<?php

declare(strict_types=1);

namespace Supergnaw\Nestbox;

use Supergnaw\Nestbox\Exception\InvalidSchemaSyntaxException;
use Supergnaw\Nestbox\Exception\InvalidWhereOperator;
use Supergnaw\Nestbox\Exception\MissingParametersException;

trait InputValidationTrait
{
    public static function sanitize_parameters(array $params): array
    {
        $output = [];

        foreach ($params as $col => $val) $output[self::valid_schema_string($col)] = $val;

        return $output;
    }


    public static function sanitize_conjunction(string $conjunction): string
    {
        return (preg_match("/^(and|or)$/i", trim($conjunction), $con)) ? $con[0] : "AND";
    }

    public static function validate_parameters(string $query, array $params): array
    {
        $output = [];
        $missing = [];

        foreach ($params as $key => $value) {
            // verify parameter is in query
            if (!strpos($query, ":$key")) continue;

            // verify parameter is a valid schema string
            if (!$key = self::valid_schema_string($key)) throw new InvalidSchemaSyntaxException($key);

            $output[$key] = $value;
        }

        // find named parameters without a defined associated value
        preg_match_all('/:(\w+)/', $query, $matches);
        foreach ($matches[1] as $column) {
            if (array_key_exists($column, $params)) continue;
            $missing[] = $column;
        }

        if ($missing) {
            throw new MissingParametersException(implode(", ", $missing));
        }

        return $output;
    }


    public static function remove_unused_parameters(string $query, array $params): array
    {
        $output = [];

        foreach ($params as $key => $value) {
            if (!$key = self::valid_schema_string($key)) continue;

            if (strpos($query, ":$key")) $output[$key] = $value;
        }

        return $output;
    }


    /**
     * Searches for paramater :names in a query and removes unused column => value pairs
     *
     * @param string $query
     * @param array $params
     * @return array
     */
    public static function verify_parameters(string $query, array $params): array
    {
        foreach ($params as $var => $val) {
            // remove unwanted parameters
            if (!strpos($query, ":$var")) {
                unset($params[$var]);
            }
        }
        return $params;
    }

    public static function compile_parameterized_fields(array $params, string $conjunction = "AND", bool $useComma = false): string
    {
        $output = [];

        foreach ($params as $key => $value) {
            $output[] = "`$key` = :$key";
        }

        if ($useComma) {
            $sep = ",";
        } else {
            $sep = (
            preg_match("/^(and|or)$/i", trim($conjunction), $con)
            ) ? strtoupper($con[0]) : "AND";
        }

        return ($output) ? implode(" $sep ", $output) : "";
    }

    public static function compile_where_clause(string $query, array $where, string $conjunction = "AND"): array
    {
        $conjunction = self::sanitize_conjunction($conjunction);

        $wheres = [];
        $params = [];

        foreach ($where as $key => $value) {
            if (!$parsed = self::parse_where_operator($key)) continue;

            list($column, $operator) = $parsed;

            if (!$column = self::valid_schema_string($column)) throw new InvalidSchemaSyntaxException($column);

            if ($exists = substr_count($query, ":$column")) {
                while (array_key_exists("{$column}_$exists", $params)) $exists++;
                $column = "{$column}_$exists";
            }

            $wheres[] = "`$column` $operator :$column";
            $params[$column] = $value;

        }

        $wheres = implode($conjunction, $wheres);

        return [$wheres, $params];
    }

    public static function parse_where_operator(string $input): array|bool
    {
        if (!preg_match('/^(\w+)\s([<!=>]+|between|like|in)/i', $input, $matches)) {
            $matches = [$input, self::valid_schema_string($input), "="];
        }

        if (!in_array(strtoupper($matches[2]), ["=", ">", "<", ">=", "<=", "<>", "!=", "BETWEEN", "LIKE", "IN"])) {
            throw new InvalidWhereOperator($matches[2]);
        }

        return [$matches[1], strtoupper($matches[2])];
    }
}