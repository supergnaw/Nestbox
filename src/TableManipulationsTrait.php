<?php

declare(strict_types=1);

namespace Supergnaw\Nestbox;

use Supergnaw\Nestbox\Exception\DuplicateTableException;
use Supergnaw\Nestbox\Exception\InvalidTableException;

trait TableManipulationsTrait
{
    /**
     * Renames table named `$oldTable` to `$newTable`
     *
     * @param string $oldTable
     * @param string $newTable
     * @return bool
     */
    public function rename_table(string $oldTable, string $newTable): bool
    {
        if (!$this->valid_schema($oldTable)) throw new InvalidTableException($oldTable);

        if ($this->valid_schema($newTable)) throw new DuplicateTableException($newTable);

        if (!$newTable = $this::valid_schema_string($newTable)) return false;

        return $this->query_execute("RENAME TABLE `$oldTable` TO `$newTable`;");
    }

    /**
     * Truncates table `$table`
     *
     * @param string $table
     * @return int|bool
     */
    public function truncate_table(string $table): int|bool
    {
        if (!$this->valid_schema($table)) throw new InvalidTableException($table);

        return $this->query_execute("TRUNCATE TABLE `table`;");
    }

    /**
     * Drops table `$table`
     *
     * @param string $table
     * @return bool
     */
    public function drop_table(string $table): bool
    {
        if (!$this->valid_schema($table)) throw new InvalidTableException($table);

        return $this->query_execute("DROP TABLE `table`;");
    }
}