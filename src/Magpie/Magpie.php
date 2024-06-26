<?php

declare(strict_types=1);

namespace NestboxPHP\Nestbox\Magpie;

use NestboxPHP\Nestbox\Nestbox;

class Magpie extends Nestbox
{
    final protected const string PACKAGE_NAME = 'magpie';
    // settings variables
    public string $magpieUsersTable = 'users';
    public string $magpieUserColumn = 'username';
    public string $magpiePermissionsTable = 'magpie_permissions';
    public string $magpiePermissionAssignmentsTable = 'magpie_permission_assignments';
    public string $magpieRolesTable = 'magpie_roles';
    public string $magpieRoleAssignmentsTable = 'magpie_role_assignments';

    public function create_tables(): void
    {
        $this->create_permissions_table();
        $this->create_roles_table();
        $this->create_permission_assignments_table();
    }

    private function create_class_table_magpie_permissions(): bool
    {
        $sql = "CREATE TABLE IF NOT EXISTS `{$this->magpiePermissionsTable}` (
                    `permission_id` INT NOT NULL AUTO_INCREMENT ,
                    `permission_name` VARCHAR(63) NOT NULL ,
                    `permission_description` VARCHAR(255) NOT NULL ,
                    PRIMARY KEY (`permission_id`)) ENGINE = InnoDB; 
                ) ENGINE = InnoDB DEFAULT CHARSET=UTF8MB4 COLLATE=utf8mb4_general_ci;";

        return $this->query_execute(query: $sql);
    }

    private function create_class_table_magpie_permission_assignments(): bool
    {
        $sql = "CREATE TABLE IF NOT EXISTS `{$this->magpiePermissionAssignmentsTable}` (
                    `assignment_id` INT NOT NULL AUTO_INCREMENT ,
                    `permission_id` INT NOT NULL ,
                    `user_id` VARCHAR( 125 ) NOT NULL ,
                    PRIMARY KEY ( `assignment_id` )
                ) ENGINE = InnoDB DEFAULT CHARSET=UTF8MB4 COLLATE=utf8mb4_general_ci;";

        return $this->query_execute(query: $sql);
    }

    private function create_class_table_magpie_roles(): bool
    {
        $sql = "CREATE TABLE IF NOT EXISTS `{$this->magpieRolesTable}` (
                    `role_id` INT NOT NULL AUTO_INCREMENT ,
                    `role_name` VARCHAR(63) NOT NULL ,
                    `role_description` VARCHAR(255) NOT NULL ,
                    PRIMARY KEY (`role_id`)) ENGINE = InnoDB; 
                ) ENGINE = InnoDB DEFAULT CHARSET=UTF8MB4 COLLATE=utf8mb4_general_ci;";

        return $this->query_execute(query: $sql);
    }

    private function create_class_table_magpie_role_assignments(): bool
    {
        $sql = "CREATE TABLE IF NOT EXISTS `{$this->magpieRoleAssignmentsTable}` (
                    `assignment_id` INT NOT NULL AUTO_INCREMENT ,
                    `permission_id` INT NOT NULL ,
                    `user_id` VARCHAR( 125 ) NOT NULL ,
                    PRIMARY KEY ( `assignment_id` )
                ) ENGINE = InnoDB DEFAULT CHARSET=UTF8MB4 COLLATE=utf8mb4_general_ci;";

        return $this->query_execute(query: $sql);
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
