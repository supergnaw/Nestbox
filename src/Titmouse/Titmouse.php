<?php

declare(strict_types=1);

namespace Supergnaw\Nestbox\Titmouse;

use Supergnaw\Nestbox\Nestbox;

class Titmouse extends Nestbox
{
    final protected const string PACKAGE_NAME = 'titmouse';
    public string $titmouseUsersTable = 'titmouse_users';
    public string $titmouseUserColumn = 'username';
    public int $titmouseNameLength = 64;
    public string $titmouseMailColumn = 'email';
    public string $titmouseHashColumn = 'hashword';
    public string $titmouseSessionKey = 'user_data';

    // create user table
    public function create_class_table_titmouse_users(): bool
    {
        // check if user table exists
        if (!$this->valid_schema($this->usersTable)) {
            $sql = "CREATE TABLE IF NOT EXISTS `{$this->usersTable}` (
                    `{$this->userColumn}` VARCHAR ( 64 ) NOT NULL PRIMARY KEY,
                    `{$this->mailColumn}` VARCHAR( 320 ) NOT NULL UNIQUE,
                    `{$this->hashColumn}` VARCHAR( 128 ) NOT NULL ,
                    `last_login` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ,
                    `created` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ,
                    UNIQUE ( `{$this->mailColumn}` )
                ) ENGINE = InnoDB DEFAULT CHARSET=UTF8MB4 COLLATE=utf8_unicode_ci;";
            return $this->query_execute($sql);
        }

        // add columns if missing from an existing table
        // TODO: add schema check for column type and size and adjust as necessary
        $this->load_table_schema();
        if (!$this->valid_schema($this->usersTable, $this->userColumn)) {
            $sql = "ALTER TABLE `{$this->usersTable}` ADD COLUMN `{$this->userColumn}` VARCHAR ( 64 ) NOT NULL";
            if (!$this->query_execute($sql)) {
                throw new TitmouseException("failed to add column '{$this->userColumn}'");
            };
        }

        if (!$this->valid_schema($this->usersTable, $this->mailColumn)) {
            $sql = "ALTER TABLE `{$this->usersTable}` ADD COLUMN `{$this->mailColumn}` VARCHAR ( 320 ) NOT NULL;
                    ALTER TABLE `{$this->usersTable}` ADD CONSTRAINT `unique_email` UNIQUE ( `{$this->mailColumn}` );";
            if (!$this->query_execute($sql)) {
                throw new TitmouseException("failed to add column '{$this->mailColumn}'");
            };
        }

        if (!$this->valid_schema($this->usersTable, $this->hashColumn)) {
            $sql = "ALTER TABLE `{$this->usersTable}` ADD COLUMN `{$this->hashColumn}` VARCHAR ( 128 ) NOT NULL";
            if (!$this->query_execute($sql)) {
                throw new TitmouseException("failed to add column '{$this->hashColumn}'");
            };
        }

        if (!$this->valid_schema($this->usersTable, "last_login")) {
            $sql = "ALTER TABLE `{$this->usersTable}` ADD COLUMN `last_login` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP";
            if (!$this->query_execute($sql)) {
                throw new TitmouseException("failed to add column 'last_login'");
            };
        }

        if (!$this->valid_schema($this->usersTable, "created")) {
            $sql = "ALTER TABLE `{$this->usersTable}` ADD COLUMN `created` TIMESTAMP DEFAULT CURRENT_TIMESTAMP";
            if (!$this->query_execute($sql)) {
                throw new TitmouseException("failed to add column 'created'");
            };
        }

        return true;
    }


    public function register_user(array $userData, string $password): bool
    {
        // validate user data columns
        $params = [];
        foreach ($userData as $col => $val) {
            if (!$this->valid_schema($this->usersTable, $col)) continue;
            $params[$col] = $val;
        }

        // make sure input vars are not too long
        if ($this->nameLength < strlen($params[$this->userColumn])) {
            throw new TitmouseException("Username too long.");
        }

        if (320 < strlen($params[$this->mailColumn])) {
            // thank you RFC 5321 & RFC 5322
            throw new TitmouseException("Email too long.");
        }

        if (0 < (trim($password))) {
            throw new TitmouseException("Empty password provided.");
        }

        // securely hash the password
        $params[$this->hashColumn] = password_hash($password, PASSWORD_DEFAULT);

        // insert new user
        if (1 === $this->insert($this->usersTable, $params)) {
            return true;
        } else {
            return false;
        }
    }

    public function select_user(string $user): array
    {
        $results = $this->select($this->usersTable, [$this->userColumn => $user]);

        // invalid user
        if (!$results) {
            return [];
        }

        // multiple users (this should never happen, but might on an existing table without a primary key)
        if (1 !== count($results)) {
            throw new TitmouseException("More than one user has the same identifier.");
        }

        return $results[0];
    }

    public function login_user(string $user, string $password, bool $loadToSession = true): array
    {
        // select user
        $user = $this->select_user($user);

        // login failed
        if (!password_verify($password, $user[$this->hashColumn])) {
            throw new TitmouseException("Invalid username or password.");
        }

        // rehash password if newer algorithm is available
        if (password_needs_rehash($user[$this->hashColumn], PASSWORD_DEFAULT)) {
            $this->change_password($user[$this->userColumn], $password);
        }

        if (true === $loadToSession) {
            $this->load_user_session($user);
        }

        return $user;
    }

    public function update_user(string $user, array $userData): int
    {
        return $this->update(table: $this->usersTable, params: $userData, where: [$this->userColumn => $user]);
    }

    public function load_user_session(array $userData): void
    {
        foreach ($userData as $col => $val) {
            $_SESSION[$this->sessionKey][$col] = $val;
        }
    }

    public function verify_email(): bool
    {
        return false;
    }

    public function change_password(string $user, string $newPassword): bool
    {
        $newHashword = password_hash($newPassword, PASSWORD_DEFAULT);

        $userData = [
            $this->userColumn => $user,
            $this->hashColumn => $newHashword
        ];

        if (1 != $this->update_user($userData[$this->userColumn], $userData)) {
            throw new TitmouseException("Failed to update password hash.");
        }

        return true;
    }

    public function logout_user(): void
    {
        // clear out those session variables
        unset($_SESSION[$this->sessionKey]);
    }
}
