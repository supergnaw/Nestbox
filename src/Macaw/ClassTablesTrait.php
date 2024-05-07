<?php

declare(strict_types=1);

namespace Supergnaw\Nestbox\Macaw;

trait ClassTablesTrait
{
    // create api call logging table
    public function create_class_table_macaw_api_log(): bool
    {
        if ($this->valid_schema("macaw_api_log")) return true;

        $sql = "CREATE TABLE IF NOT EXISTS `macaw_api_calls` (
            `call_id` INT NOT NULL AUTO_INCREMENT ,
            `call_endpoint` VARCHAR( 64 ) NULL ,
            `call_client` VARCHAR( 64 ) NULL ,
            `call_time` DATETIME DEFAULT CURRENT_TIMESTAMP ,
            `status_code` VARCHAR( 3 ) ,
            PRIMARY KEY ( `call_id` )
        ) ENGINE = InnoDB DEFAULT CHARSET=UTF8MB4 COLLATE=utf8mb4_general_ci;";
        return $this->query_execute($sql);
    }
}