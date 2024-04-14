<?php

declare(strict_types=1);

namespace Supergnaw\Nestbox;

use Supergnaw\Nestbox\Exception\InvalidSchemaSyntaxException;
use Supergnaw\Nestbox\Exception\InvalidTableException;

trait SchemaTrait
{
    protected array $tableSchema = [];
    protected array $triggerSchema = [];

    public function table_schema(): array
    {
        $this->load_table_schema();
        return $this->tableSchema;
    }

    /**
     * Load table schema
     *
     * @return bool
     */
    public function load_table_schema(): bool
    {
        $params = array();

        $sql = "SELECT `TABLE_NAME`,`COLUMN_NAME`,`DATA_TYPE`
                FROM `INFORMATION_SCHEMA`.`COLUMNS`
                WHERE `TABLE_SCHEMA` = :database_name;";

        if (!$this->query_execute($sql, ['database_name' => $this->name])) {
            return false;
        }

        foreach ($this->results() as $row) {
            $this->tableSchema[$row['TABLE_NAME']][$row['COLUMN_NAME']] = $row['DATA_TYPE'];
        }

        return true;
    }

    /**
     * Load trigger schema
     *
     * @return bool
     */
    public function load_trigger_schema(): bool
    {
        $sql = "SELECT `TRIGGER_NAME`, `EVENT_OBJECT_TABLE`
                FROM `INFORMATION_SCHEMA`.`TRIGGERS`
                WHERE `TRIGGER_SCHEMA` = '{$this->name}';";

        if (!$this->query_execute($sql, ['database_name' => $this->name])) return false;

        foreach ($this->results() as $row) {
            if (!in_array($row['EVENT_OBJECT_TABLE'], $this->triggerSchema)) {
                $this->triggerSchema[$row['EVENT_OBJECT_TABLE']] = [];
            }
            $this->triggerSchema[$row['EVENT_OBJECT_TABLE']][] = $row['TRIGGER_NAME'];
        }

        return true;
    }

    /**
     * Determine if a given table/column combination exists
     * within the database schema
     *
     * @param string $tbl
     * @param string|null $col
     * @return bool
     */
    public function valid_schema(string $table, string $column = null): bool
    {
        if (empty($this->tableSchema)) $this->load_table_schema();

        $table = $this->valid_schema_string($table);
        $col = ($col = trim($col ?? "")) ? $this->valid_schema_string($col) : $col;

        // check table
        if (!array_key_exists($table, $this->tableSchema)) {
            if (empty($this->tableSchema)) $this->load_table_schema();
            if (!array_key_exists($table, $this->tableSchema)) return false;
        }
        if (empty($col)) return true;

        // check column
        return array_key_exists($col, $this->tableSchema[$table]);
    }

    /**
     * Determine if a table exists within the database
     *
     * @param string $table
     * @return bool
     */
    public function valid_table(string $table): bool
    {
        return $this->valid_schema(table: $table);
    }

    /**
     * Determine if a column exists within a given table
     *
     * @param string $table
     * @param string $column
     * @return bool
     */
    public function valid_column(string $table, string $column): bool
    {
        return $this->valid_schema(table: $table, column: $column);
    }

    /**
     * Determine if a trigger exists within a given table
     *
     * @param string $table
     * @param string $trigger
     * @return bool
     */
    public function valid_trigger(string $table, string $trigger): bool
    {
        if (empty($this->triggerSchema)) {
            $this->load_trigger_schema();
        }

        if (!$this->valid_schema(table: $table)) return false;

        if (in_array(needle: $trigger, haystack: $this->triggerSchema[$table] ?? [])) return true;

        // reload in case schema has changed since last load
        $this->load_trigger_schema();
        return in_array(needle: $trigger, haystack: $this->triggerSchema[$table] ?? []);
    }

    public function valid_schema_string(string $string): string
    {
        if (!preg_match(pattern: "/^\w+$/i", subject: trim($string), matches: $matches)) {
            throw new InvalidSchemaSyntaxException($string);
        }

        return $matches[0];
    }

    /**
     * Get primary key for a given table within the database
     *
     * @param string $table
     * @return string
     */
    public function table_primary_key(string $table): string
    {
        if (!$this->valid_schema($table)) {
            throw new InvalidTableException($table);
        }

        $sql = "SHOW KEYS FROM `{$table}` WHERE `Key_name` = 'PRIMARY';";
        if ($this->query_execute($sql)) {
            $rows = $this->results(firstResultOnly: true);
            return $rows["Column_name"];
        } else {
            return "";
        }
    }
}