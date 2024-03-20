<?php
declare(strict_types=1);

namespace app\Nestbox\Titmouse;

use app\Nestbox\Exception\InvalidColumnException;
use app\Nestbox\Exception\InvalidTableException;
use app\Nestbox\Exception\NestboxException;
use app\Nestbox\Nestbox;

class Titmouse extends Nestbox
{
    private const DEFAULT_USERS_TABLE = 'users';
    private const DEFAULT_USER_COLUMN = 'email';
    private const DEFAULT_HASH_COLUMN = 'hashword';
    private const DEFAULT_SESSION_KEY = 'user_data';
    private string $usersTable;
    private string $userColumn;
    private string $hashColumn;
    private string $sessionKey;
    private string $permissionsTable;

    public function __construct(
        string $usersTable = "users",
        string $userColumn = "email",
        string $hashColumn = "hashword",
        string $sessionKey = "user_data",
        string $permsTable = "user_permissions"
    )
    {
        // start session if unstarted
        if (PHP_SESSION_ACTIVE !== session_status()) {
            session_start();
        }

        // prepare class variables
        parent::__construct();
        $this->set_session_key($sessionKey);

        // create class tables
        $this->create_user_table(usersTable: $usersTable, userColumn: $userColumn, hashColumn: $hashColumn);
        $this->create_permission_table(permissionsTable: $permsTable);
    }

    public function __invoke(string $host = null, string $user = null, string $pass = null, string $name = null)
    {
        parent::__construct($host, $user, $pass, $name);
    }

    public function set_users_table(string $table): bool
    {
        if (!$this->valid_schema($table)) {
            throw new InvalidTableException("Failed to find table: {$table}");
        } else {
            $this->usersTable = $table;
            return true;
        }
    }

    public function set_permissions_table(string $table): bool
    {
        if (!$this->valid_schema($table)) {
            throw new InvalidTableException("Failed to find permissions table: {$table}");
        } else {
            $this->permissionsTable = $table;
            return true;
        }
    }

    public function users_table(): string
    {
        return $this->usersTable;
    }

    public function user_column(): string
    {
        return $this->userColumn;
    }

    public function hash_column(): string
    {
        return $this->hashColumn;
    }

    public function session_key(): string
    {
        return $this->sessionKey;
    }

    public function set_user_column(string $column): bool
    {
        if (!$this->valid_schema($this->usersTable, $column)) {
            throw new InvalidColumnException("Invalid column defiened for table: {$this->usersTable}.{$column}");
        } else {
            $this->userColumn = $column;
            return true;
        }
    }

    public function set_hash_column(string $column): bool
    {
        if (!$this->valid_schema($this->usersTable, $column)) {
            throw new InvalidColumnException("Invalid column defiened for table: {$this->usersTable}.{$column}");
        } else {
            $this->hashColumn = $column;
            return true;
        }
    }

    public function set_session_key(string $sessionKey): bool
    {
        if ($this->sessionKey = $sessionKey) {
            return true;
        }
        return false;
    }

    // create entry table
    public function create_user_table(string $usersTable, string $userColumn, string $hashColumn): bool
    {
        // check if entry table exists
        if (!$this->valid_schema($usersTable)) {
            $sql = "CREATE TABLE IF NOT EXISTS `{$usersTable}` (
                        `username` VARCHAR ( 64 ) NOT NULL ,
                        `email` VARCHAR( 64 ) NOT NULL ,
                        `hashword` VARCHAR( 128 ) NOT NULL ,
                        `last_login` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ,
                        `created` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ,
                        `permissions` INT( 11 ) NOT NULL DEFAULT 0
                        PRIMARY KEY ( `username` ) ,
                        UNIQUE KEY `email` ( `email` )
                    ) ENGINE = InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
            if (!$this->query_execute($sql)) return false;
        }

        // add columns if missing from an existing table
        if (!$this->valid_schema($usersTable, "username")) {
            $sql = "ALTER TABLE `{$usersTable}` ADD COLUMN `username` VARCHAR ( 64 ) NOT NULL";
            if (!$this->query_execute($sql)) return false;
        }
        if (!$this->valid_schema($usersTable, "email")) {
            $sql = "ALTER TABLE `{$usersTable}` ADD COLUMN `email` VARCHAR ( 64 ) NOT NULL";
            if (!$this->query_execute($sql)) return false;
        }
        if (!$this->valid_schema($usersTable, "hashword")) {
            $sql = "ALTER TABLE `{$usersTable}` ADD COLUMN `hashword` VARCHAR ( 128 ) NOT NULL";
            if (!$this->query_execute($sql)) return false;
        }
        if (!$this->valid_schema($usersTable, "last_login")) {
            $sql = "ALTER TABLE `{$usersTable}` ADD COLUMN `last_login` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP";
            if (!$this->query_execute($sql)) return false;
        }
        if (!$this->valid_schema($usersTable, "created")) {
            $sql = "ALTER TABLE `{$usersTable}` ADD COLUMN `created` TIMESTAMP DEFAULT CURRENT_TIMESTAMP";
            if (!$this->query_execute($sql)) return false;
        }
        if (!$this->valid_schema($usersTable, "permissions")) {
            $sql = "ALTER TABLE `{$usersTable}` ADD COLUMN `permissions` INT ( 11 ) NOT NULL DEFAULT 0";
            if (!$this->query_execute($sql)) return false;
        }

        if (!$this->set_users_table($usersTable)) return false;
        if (!$this->set_user_column($userColumn)) return false;
        if (!$this->set_hash_column($hashColumn)) return false;

        return true;
    }

    public function create_permission_table($permissionsTable): bool
    {
        $triggerName = "calculate_permission_value";

        // check if permission table with trigger exists
        if ($this->valid_trigger($permissionsTable, $triggerName)) return true;

        $sql = "CREATE TABLE IF NOT EXISTS `{$permissionsTable}` (
                    `permission_id` INT NOT NULL AUTO_INCREMENT ,
                    `permission_value` INT NULL ,
                    `permission_category` VARCHAR(31) NOT NULL , 
                    `permission_name` VARCHAR(63) NOT NULL , 
                    `permission_description` VARCHAR(255) NOT NULL , 
                    PRIMARY KEY (`permission_id`)) ENGINE = InnoDB; 
                ) ENGINE = InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
        if (!$this->query_execute($sql)) return false;
        $this->set_permissions_table($permissionsTable);

        $sql = "DELIMITER $$
                DROP TRIGGER IF EXISTS `{$triggerName}`$$
                CREATE DEFINER=`{$this->user}`@`{$this->pass}` TRIGGER `{$triggerName}`
                BEFORE INSERT ON `{$permissionsTable}`
                FOR EACH ROW BEGIN
                    DECLARE calculated_perm_value INT DEFAULT 0;
                    SELECT `auto_increment` INTO calculated_perm_value
                    FROM `information_schema`.`tables`
                    WHERE `table_name` = '{$this->users_table()}'
                    AND `table_schema` = '{$this->name}';
                    SET NEW.permission_value = POWER(2, calculated_perm_value - 1)
                $$
                END
                DELIMITER ;";
        $sql = "CREATE TRIGGER `calculate_permission_value`
                BEFORE INSERT ON `{$permissionsTable}`
                FOR EACH ROW
                    SET NEW.permission_value = POWER(2, NEW.permission_id - 1);";

        if (!$this->query_execute($sql)) return false;
        if ($this->valid_trigger(table: $permissionsTable, trigger: $triggerName)) return true;

        $this->query_execute("DROP TABLE `$permissionsTable`;");
        return false;
    }

    public function register_user(array $userData, string $password): bool
    {
        // validate user compendium columns
        $params = [];
        foreach ($userData as $col => $val) {
            if (!$this->valid_schema($this->usersTable, $col)) {
                throw new InvalidColumnException("Invalid column: {$col}");
            } else {
                $params[$col] = $val;
            }
        }

        // securely hash the password
        if (empty($password)) {
            throw new NestboxException("Empty password provided.");
        }
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
        return $this->select($this->users_table(), [$this->user_column() => $user]);
    }

    public function login_user(string $user, string $password): bool
    {
        // select user
        $user = $this->select_user($user);

        // invalid user
        if (!$user) {
            return false;
        }

        // multiple users (this should never happen, but might on an existing table without a primary key)
        if (1 !== count($user)) {
            throw new NestboxException("More than one user has the same identifier.");
        }

        // login failed
        if (!password_verify($password, $user[0][$this->hashColumn])) {
            throw new NestboxException("Invalid username or password.");
        }

        // rehash password if newer algorithm is available
        if (password_needs_rehash($user[0][$this->hashColumn], PASSWORD_DEFAULT)) {
            $hashword = password_hash($password, PASSWORD_DEFAULT);
            $userData = [
                'user' => $user,
                'hashword' => $hashword
            ];
            if ($this->edit_user($userData[$this->userColumn], $userData)) {
                // reload the user, although it shouldn't change anything
                $user = $this->select($this->usersTable, [$this->userColumn => $user]);
            }
        }

        $this->load_user_session($user[0]);

        return true;
    }

    // todo: check usage and change name to update_user and adjust return value to int instead of bool
    public function edit_user($user, $userData): bool
    {
        if (false !== $this->update(table: $this->usersTable, params: $userData, where: [$this->userColumn => $user])) {
            return true;
        } else {
            return false;
        }
    }

    public function load_user_session(array $user): void
    {
        foreach ($user as $col => $val) {
            $_SESSION[$this->sessionKey][$col] = $val;
        }
    }

    public function logout_user(): void
    {
        foreach ($_SESSION[$this->sessionKey] as $key => $val) {
            unset($_SESSION[$this->sessionKey][$key]);
        }
        unset($_SESSION[$this->sessionKey]);
    }

    public function verify_email(): bool
    {
        return false;
    }

    public function reset_password(): bool
    {
        return false;
    }
}

class user
{
    public function __construct(string $user_id) {

    }
}
