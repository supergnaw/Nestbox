<?php

declare(strict_types=1);

namespace Supergnaw\Nestbox;

use Supergnaw\Nestbox\Exception\InvalidTableException;

trait TableManipulationsTrait
{
    public function truncate_table(string $table): int|bool
    {
        if (!$this->valid_schema($table)) throw new InvalidTableException($table);

        return $this->query_execute("TRUNCATE TABLE `table`;");
    }

    public function drop_table(string $table): bool
    {
        if (!$this->valid_schema($table)) throw new InvalidTableException($table);

        return $this->query_execute("DROP TABLE `table`;");
    }
}