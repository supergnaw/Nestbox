<?php

declare(strict_types=1);

namespace Supergnaw\Nestbox\Magpie;

use Supergnaw\Nestbox\Nestbox;

class Magpie extends Nestbox
{
    public array $errors = [];

    // constructor
    public function __construct(string $image_directory = null)
    {
        // database functions
        parent::__construct();
        // create class tables
        // $this->create_lorikeet_tables();
    }

    private function create_magpie_tables(): bool
    {
        // check if entry table exists
        if (!$this->valid_schema('lorikeet_images')) {
            $sql = "CREATE TABLE IF NOT EXISTS `lorikeet_images` (
                        `image_id` VARCHAR( 64 ) NOT NULL ,
                        `image_title` VARCHAR( 128 ) NOT NULL ,
                        `image_caption` VARCHAR( 256 ) NULL ,
                        `edited` TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL DEFAULT CURRENT TIMESTAMP ,
                        `tags` MEDIUMTEXT NOT NULL ,
                        PRIMARY KEY ( `image_id` )
                    ) ENGINE = InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
            if (!$this->query_execute($sql)) return false;
        }

        return true;
    }

    // todo: use Nestbox->load_settings() instead
    private function load_settings(): bool
    {
        return true;
    }

    public function permission_create(): bool
    {
        // create snake_case id from permission name
        // check no other permission with name exists
        // add permission to permissions table
        return true;
    }

    public function permission_rename(): bool
    {
        // check no other permission with name exists
        // update permission name and id
        // update permission assignments for roles
        // update permission assignments for users
        return true;
    }

    public function permission_delete(): bool
    {
        // delete permission form permissions table
        // delete assigned permissions from users
        // delete grouped permissions from roles
        return true;
    }

    public function role_create(): bool
    {
        // create snake_case id from role name
        // check no other role with name exists
        // add role to role table
        return true;
    }

    public function role_rename(): bool
    {
        // check no other role with name exists
        // update role name and id
        // update role assignment names for users
        return true;
    }

    public function role_delete(): bool
    {
        // delete role from roles table
        // delete assigned roles from users
        return true;
    }

    public function role_add_permission(): bool
    {
        return true;
    }

    public function role_remove_permission(): bool
    {
        return true;
    }

    public function user_add_permission(): bool
    {
        return true;
    }

    public function user_remove_permission(): bool
    {
        return true;
    }

    public function user_add_role(): bool
    {
        return true;
    }

    public function user_remove_role(): bool
    {
        return true;
    }
}
