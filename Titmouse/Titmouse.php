<?php
declare(strict_types=1);

namespace app\Nestbox\Titmouse;

use app\Nestbox\Exception\InvalidColumnException;
use app\Nestbox\Exception\InvalidTableException;
use app\Nestbox\Exception\NestboxException;
use app\Nestbox\Nestbox;

class Titmouse extends Nestbox
{
    private $usersTable;
    private $userColumn;
    private $hashColumn;
    private $sessionKey;

    public function __construct(
        string $usersTable = "users",
        string $userColumn = "email",
        string $hashColumn = "hashword",
        string $sessionKey = "user_data"
    )
    {
        // start session if unstarted
        if (PHP_SESSION_ACTIVE !== session_status()) {
            session_start();
        }

        // prepare class variables
        parent::__construct();

        // create users table
        $this->create_user_table($usersTable);

        // validate user schema and set variables
        $this->set_users_table($usersTable);
        $this->set_user_column($userColumn);
        $this->set_hash_column($hashColumn);
        $this->set_session_key($sessionKey);
    }

    public function __invoke(string $host = null, string $user = null, string $pass = null, string $name = null)
    {
        parent::__construct($host, $user, $pass, $name);
    }

    public function set_users_table(string $table): bool
    {
        if (!$this->valid_schema($table)) {
            throw new InvalidTableException("Invalid schema defined.");
        } else {
            $this->usersTable = $table;
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
            throw new InvalidColumnException("Invalid schema defined.");
        } else {
            $this->userColumn = $column;
            return true;
        }
    }

    public function set_hash_column(string $column): bool
    {
        if (!$this->valid_schema($this->usersTable, $column)) {
            throw new InvalidColumnException("Invalid schema defined.");
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
    public function create_user_table($usersTable): bool
    {
        // check if entry table exists
        if ($this->valid_schema($usersTable)) return true;

        $sql = "CREATE TABLE IF NOT EXISTS `{$usersTable}` (
                        `username` VARCHAR ( 64 ) NOT NULL
                        `email` VARCHAR( 64 ) NOT NULL ,
                        `hashword` VARCHAR( 128 ) NOT NULL ,
                        `last_login` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ,
                        `created` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ,
                        PRIMARY KEY ( `username` ) ,
                        UNIQUE KEY `email` ( `email` )
                    ) ENGINE = InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
        return $this->query_execute($sql);
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
        var_dump($user);
        if ($user) {
            // verify user
            if (1 !== count($user)) {
                throw new NestboxException("More than one user has the same identifier.");
            } else {
                if (password_verify($password, $user[0][$this->hashColumn])) {
                    // login successful
                    if (password_needs_rehash($user[0][$this->hashColumn], PASSWORD_DEFAULT)) {
                        // If newer hashing algorithm is available, create a new hash, and replace the old one
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
                } else {
                    // login failed
                    throw new NestboxException("Invalid username or password.");
                }
            }
        } else {
            return false;
        }
    }

    public function edit_user($user, $userData): bool
    {
        if (false !== $this->update($this->usersTable, $userData, [$this->userColumn => $user])) {
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
