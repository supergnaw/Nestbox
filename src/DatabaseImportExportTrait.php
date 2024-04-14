<?php

declare(strict_types=1);

namespace Supergnaw\Nestbox;

use Supergnaw\Nestbox\Exception\InvalidTableException;
use Supergnaw\Nestbox\Exception\MalformedJsonException;

trait DatabaseImportExportTrait
{
    public function dump_table(string $table): array
    {
        return $this->select($table);
    }

    public function dump_database(array $tables = []): array
    {
        if (empty($tables)) $tables = array_keys($this->table_schema());

        $output = [];

        foreach ($tables as $table) $output[$table] = $this->dump_table(table: $table);

        return $output;
    }

    public function load_table(string $table, string|array $data): int
    {
        $updateCount = 0;

        if (!$this->valid_schema($table)) throw new InvalidTableException($table);

        if (is_string($data)) {
            if (!json_validate($data)) throw new MalformedJsonException;

            $data = json_decode($data, associative: true);
        }

        return $this->insert(table: $table, params: $data, update: true);
    }

    public function load_database(string|array $input): int
    {
        $updateCount = 0;

        if (is_string($input)) {
            if (!json_validate($input)) throw new MalformedJsonException;

            $input = json_decode($input, associative: true);
        }

        foreach ($input as $table => $data) {
            $updateCount += $this->load_table(table: $table, data: $data);
        }

        return $updateCount;
    }
}