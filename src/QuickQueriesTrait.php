<?php

declare(strict_types=1);

namespace Supergnaw\Nestbox;

use Supergnaw\Nestbox\Exception\EmptyParamsException;
use Supergnaw\Nestbox\Exception\InvalidSchemaSyntaxException;
use Supergnaw\Nestbox\Exception\InvalidTableException;

trait QuickQueriesTrait
{

    /**
     * Inserts one or more rows of data `$params`
     *
     * @param string $table
     * @param array $params
     * @param bool $update
     * @return int
     */
    public function insert(string $table, array $params, bool $update = true): int|bool
    {
        // verify table
        if (!$this::valid_schema_string($table)) throw new InvalidSchemaSyntaxException($table);

        // verify params
        if (empty($params)) throw new EmptyParamsException("Cannot insert empty data into table.");

        $columns = $this::compile_column_list($params);

        $cols = implode("`, `", $columns);

        list($named, $params) = $this::compile_values_list($params);

        $named = "( " . implode(" ), ( ", $named) . " )";

        $sql = "INSERT INTO `$table` ( `$cols` ) VALUES $named";

        // add updates
        if (true === $update) {
            $primaryKey = $this->table_primary_key($table);

            $updates = $this::compile_update_list($table, $columns, $primaryKey);

            $sql .= " AS `new` ON DUPLICATE KEY UPDATE " . implode(", ", $updates);
        }

        // execute query
        if (!$this->query_execute($sql, $params)) return false;

        return $this->row_count();
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
    public function update(string $table, array $updates, array $where = [], string $conjunction = "AND"): int|bool
    {
        if (!$this->valid_schema($table)) throw new InvalidTableException($table);

        list($updates, $updateParams) = $this::compile_set_clause($updates);

        $sql = "UPDATE `{$table}` SET {$updates}";

        list($where, $whereParams) = $this::compile_where_clause($sql, $where, $conjunction);

        $sql .= (!$where) ? ";" : " WHERE $where;";

        if (!$this->query_execute($sql, array_merge($updateParams, $whereParams))) return false;

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
        if (!$this->valid_schema($table)) throw new InvalidTableException($table);

        $sql = "SELECT * FROM `$table`";

        list($where, $params) = $this::compile_where_clause($sql, $where, $conjunction);

        $sql .= (!$where) ? ";" : " WHERE $where;";

        return $this->results();
    }

    /**
     * Deletes rows that match `$where` conditions
     *
     * @param string $table
     * @param array $where
     * @param $conjunction
     * @return int|bool
     */
    public function delete(string $table, array $where, string $conjunction = "AND", bool $deleteAll = false): int|bool
    {
        if (!$this->valid_schema($table)) throw new InvalidTableException($table);

        $sql = "DELETE FROM `$table`";

        list($where, $params) = $this::compile_where_clause($sql, $where, $conjunction);

        $sql .= (!$where && $deleteAll) ? ";" : " WHERE $where;";

        if (!$this->query_execute($sql, $params)) return false;

        return $this->row_count();
    }
}