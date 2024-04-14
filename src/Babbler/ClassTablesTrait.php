<?php

declare(strict_types=1);

namespace Supergnaw\Nestbox\Babbler;

trait ClassTablesTrait
{
    // create entry table
    public function create_class_table_babbler_entries(): bool
    {
        // check if entry table exists
        if ($this->valid_schema('babbler_entries')) return true;

        $sql = "CREATE TABLE IF NOT EXISTS `babbler_entries` (
                    `entry_id` INT NOT NULL AUTO_INCREMENT ,
                    `created` DATETIME NOT NULL ,
                    `edited` TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ,
                    `published` DATETIME NULL ,
                    `is_draft` TINYINT( 1 ) NOT NULL DEFAULT 0 ,
                    `is_hidden` TINYINT( 1 ) NOT NULL DEFAULT 0 ,
                    `created_by` VARCHAR( {$this->babblerAuthorSize} ) NOT NULL ,
                    `edited_by` VARCHAR( {$this->babblerAuthorSize} ) NOT NULL ,
                    `category` VARCHAR( {$this->babblerCategorySize} ) NOT NULL ,
                    `sub_category` VARCHAR( {$this->babblerSubCategorySize} ) NOT NULL ,
                    `title` VARCHAR( {$this->babblerTitleSize} ) NOT NULL ,
                    `content` MEDIUMTEXT NOT NULL ,
                    PRIMARY KEY ( `entry_id` )
                ) ENGINE = InnoDB DEFAULT CHARSET=UTF8MB4 COLLATE=utf8_unicode_ci;";
        return $this->query_execute($sql);
    }

    // create history table and update trigger
    public function create_class_table_babbler_history(): bool
    {
        // check if history table exists
        if ($this->valid_schema('babbler_history')) return true;

        // create the history table
        $sql = "CREATE TABLE IF NOT EXISTS `babbler_history` (
                    `history_id` INT NOT NULL AUTO_INCREMENT ,
                    `entry_id` INT NOT NULL ,
                    `created` DATETIME NOT NULL ,
                    `edited` TIMESTAMP ,
                    `published` DATETIME NULL ,
                    `is_draft` TINYINT( 1 ) NOT NULL DEFAULT 0 ,
                    `is_hidden` TINYINT( 1 ) NOT NULL DEFAULT 0 ,
                    `created_by` VARCHAR( {$this->babblerAuthorSize} ) NOT NULL ,
                    `edited_by` VARCHAR( {$this->babblerAuthorSize} ) NOT NULL ,
                    `category` VARCHAR( {$this->babblerCategorySize} ) NOT NULL ,
                    `sub_category` VARCHAR( {$this->babblerSubCategorySize} ) NOT NULL ,
                    `title` VARCHAR( {$this->babblerTitleSize} ) NOT NULL ,
                    `content` MEDIUMTEXT NOT NULL ,
                    PRIMARY KEY ( `history_id` )
                ) ENGINE = InnoDB DEFAULT CHARSET=UTF8MB4 COLLATE=utf8_unicode_ci;";

        if (!$this->query_execute($sql)) return false;

        // create history trigger
        $sql = "CREATE TRIGGER IF NOT EXISTS `babbler_history_trigger` AFTER UPDATE ON `babbler_entries`
                FOR EACH ROW
                IF ( OLD.edited <> NEW.edited ) THEN
                    INSERT INTO `babbler_history` (
                        `entry_id`
                        , `created`
                        , `edited`
                        , `published`
                        , `is_draft`
                        , `is_hidden`
                        , `created_by`
                        , `edited_by`
                        , `category`
                        , `sub_category`
                        , `title`
                        , `content`
                    ) VALUES (
                        OLD.`entry_id`
                        , OLD.`created`
                        , OLD.`edited`
                        , OLD.`published`
                        , OLD.`is_draft`
                        , OLD.`is_hidden`
                        , OLD.`created_by`
                        , OLD.`edited_by`
                        , OLD.`category`
                        , OLD.`sub_category`
                        , OLD.`title`
                        , OLD.`content`
                    );
                END IF;
                CREATE TRIGGER `babbler_delete_trigger` BEFORE DELETE
                ON `babbler_entries` FOR EACH ROW
                BEGIN
                    INSERT INTO `babbler_history` (
                        `entry_id`
                        , `created`
                        , `edited`
                        , `published`
                        , `is_draft`
                        , `is_hidden`
                        , `created_by`
                        , `edited_by`
                        , `category`
                        , `sub_category`
                        , `title`
                        , `content`
                    ) VALUES (
                        OLD.`entry_id`
                        , OLD.`created`
                        , OLD.`edited`
                        , OLD.`published`
                        , OLD.`is_draft`
                        , OLD.`is_hidden`
                        , OLD.`created_by`
                        , OLD.`edited_by`
                        , OLD.`category`
                        , OLD.`sub_category`
                        , OLD.`title`
                        , OLD.`content`
                    );
                END;";

        // todo: check if trigger added and delete table if trigger creation fails
        return $this->query_execute($sql);
    }
}