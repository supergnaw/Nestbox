<?php

declare(strict_types=1);

namespace Supergnaw\Nestbox;

use Supergnaw\Nestbox\Exception\EmptyParamsException;
use Supergnaw\Nestbox\Exception\InvalidColumnException;
use Supergnaw\Nestbox\Exception\InvalidSchemaSyntaxException;
use Supergnaw\Nestbox\Exception\InvalidTableException;

trait QuickQueriesTrait
{

    /**
     * Inserts one or more rows with data `$params`
     *
     * @param string $table
     * @param array $params
     * @param bool $update
     * @return int
     */
    public function insert(string $table, array $params, bool $update = true): int
    {
        // verify table
        if (!$this::valid_schema_string($table)) throw new InvalidSchemaSyntaxException($table);

        if (empty($params)) throw new EmptyParamsException("Cannot insert empty data into table.");

        $multipleRows = false;

        // verify columns
        foreach ($params as $col => $val) {
            if (is_array($val)) {
                // inserting multiple rows
                foreach ($val as $c => $v) {
                    if (!$this->valid_schema($table, $c)) throw new InvalidColumnException("$table.$col");
                }
                $multipleRows = true;
            } else {
                // inserting a single row
                if (!$this->valid_schema($table, $col)) throw new InvalidColumnException("$table.$col");
                $insertMode = "single";
            }
        }

        // prepare variables
        if ($multipleRows) {
            $vars = [];
            $p = [];
            foreach ($params as $i => $row) {
                $columns = array_keys($row);
                $cols = (count($row) > 1) ? implode("`,`", $columns) : current($columns);
                $vars[] = (count($row) > 1) ? implode("_{$i}, :", $columns) . "_{$i}" : current($columns);
                foreach ($row as $c => $v) {
                    $p["{$c}_{$i}"] = $v;
                }
            }
            $vars = implode("),(:", $vars);
        } else {
            $columns = array_keys($params);
            $cols = (count($params) > 1) ? implode("`,`", $columns) : current($columns);
            $vars = (count($params) > 1) ? implode(", :", $columns) : current($columns);
        }

        // create base query
        $query = "INSERT INTO `$table` (`$cols`) VALUES (:$vars)";

        // add update
        if (true === $update) {
            $updates = [];
            $priKey = $this->table_primary_key($table);

            if ($multipleRows) {
                $updates = [];
                foreach ($params[0] as $col => $val) {
                    if ($col != $priKey) {
                        $updates[] = "`{$table}`.`{$col}` = `new`.`{$col}`";
                    }
                }
                $query .= " AS `new` ON DUPLICATE KEY UPDATE " . implode(",", $updates);
            } else {
                foreach ($params as $col => $val) {
                    if ($col != $priKey) {
                        $updates[] = "`{$col}` = :{$col}";
                    }
                }
                $query .= " ON DUPLICATE KEY UPDATE " . implode(",", $updates);
            }
        }

        $params = ($multipleRows) ? $p : $params;

        // execute query
        if ($this->query_execute($query, $params)) {
            return $this->row_count();
        } else {
            return 0;
        }
    }

    /**
     * Updates rows in `$table` with `$params` that match `$where` conditions
     *
     * @param string $table
     * @param array $params
     * @param array $where
     * @param string $conjunction
     * @return int|bool
     */
    public function update(string $table, array $params, array $where, string $conjunction): int|bool
    {
        if (!$table = $this::valid_schema_string($table)) throw new InvalidTableException($table);

        $params = $this::validate_parameters($query, $params);

        if (!$params) throw new EmptyParamsException("Cannot update with empty data.");

        $where = $this::sanitize_parameters($where);

        $updates = $this::compile_parameterized_fields($params, "", true);
        $wheres = $this::compile_parameterized_fields($where, $conjunction);

        if (!$this->query_execute("UPDATE `{$table}` SET {$updates} WHERE {$wheres};", $params)) return false;

        return $this->row_count();
    }

    /**
     * Deletes rows that match `$where` conditions
     *
     * @param string $table
     * @param array $where
     * @param $conjunction
     * @return int|bool
     */
    public function delete(string $table, array $where, $conjunction = "AND"): int|bool
    {
        if (!$table = $this::valid_schema_string($table)) throw new InvalidTableException($table);

        $params = $this::sanitize_parameters($where);
        $wheres = $this::compile_parameterized_fields($params, $conjunction);

        if (!$this->query_execute("DELETE FROM `{$table}` WHERE {$wheres};", $params)) return false;

        return $this->row_count();
    }

    /**
     * Selects all rows in `$table` or only ones that match `$where` conditions
     *
     * @param string $table
     * @param array $where
     * @param string $conjunction
     * @return array|bool
     */
    public function select(string $table, array $where = [], string $conjunction = "AND"): array|bool
    {
        if (!$table = $this::valid_schema_string($table)) throw new InvalidTableException($table);

        $params = $this::sanitize_parameters($where);
        $wheres = $this::compile_parameterized_fields($params, $conjunction);

        $sql = ($wheres) ? "SELECT * FROM `{$table}` WHERE {$wheres};" : "SELECT * FROM `{$table}`;";

        if (!$this->query_execute($sql, $params)) return false;

        return $this->results();
    }
}