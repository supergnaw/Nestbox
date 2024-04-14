<?php

declare(strict_types=1);

namespace Supergnaw\Nestbox;

trait ClassTablesTrait
{
    protected function check_class_tables(): void
    {
        foreach (get_class_methods($this) as $methodName) {
            if (preg_match('/^create_class_table_(\w+)$/', $methodName, $matches)) {
                if (!$this->valid_schema($matches[1])) $this->create_class_tables();
                return;
            }
        }
    }

    protected function create_class_tables(): void
    {
        foreach (get_class_methods($this) as $methodName) {
            if (str_starts_with(haystack: $methodName, needle: "create_class_table_")) $this->$methodName();
        }
    }
}