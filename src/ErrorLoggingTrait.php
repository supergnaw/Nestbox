<?php

declare(strict_types=1);

namespace Supergnaw\Nestbox;

trait ErrorLoggingTrait
{
    protected function create_class_table_nestbox_errors(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS `nestbox_errors` (
                    `error_id` INT NOT NULL AUTO_INCREMENT ,
                    `occurred` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ,
                    `message` VARCHAR( 512 ) NOT NULL ,
                    `query` VARCHAR( 4096 ) NOT NULL ,
                    PRIMARY KEY ( `error_id` )
                ) ENGINE = InnoDB DEFAULT CHARSET=UTF8MB4 COLLATE=utf8mb4_general_ci;";
        $this->query_execute($sql);
    }

    protected function log_error(string $message, string $query): int
    {
        $error = [
            "message" => substr(string: $message, offset: 0, length: 512),
            "query" => substr(string: $query, offset: 0, length: 4096),
        ];
        return $this->insert(table: 'nestbox_errors', params: $error);
    }
}