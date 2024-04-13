<?php
declare(strict_types=1);

namespace Supergnaw\Nestbox;

use PDO;
use PDOException;
use PDOStatement;
use Supergnaw\Nestbox\Exception\CannotBindArrayException;
use Supergnaw\Nestbox\Exception\EmptyQueryException;
use Supergnaw\Nestbox\Exception\FailedToBindValueException;
use Supergnaw\Nestbox\Exception\InvalidColumnException;
use Supergnaw\Nestbox\Exception\InvalidTableException;
use Supergnaw\Nestbox\Exception\EmptyParamsException;
use Supergnaw\Nestbox\Exception\NestboxException;
use Supergnaw\Nestbox\Exception\QueryErrorException;
use Supergnaw\Nestbox\Exception\TransactionBeginFailedException;
use Supergnaw\Nestbox\Exception\TransactionCommitFailedException;
use Supergnaw\Nestbox\Exception\TransactionException;
use Supergnaw\Nestbox\Exception\TransactionInProgressException;
use Supergnaw\Nestbox\Exception\TransactionRollbackFailedException;

class Nestbox
{
    protected const string PACKAGE_NAME = 'nestbox';

    /*
        1.0 Structs & Vars
    */

    // connection properties
    protected string $host;
    protected string $user;
    protected string $pass;
    protected string $name;

    // handler properties
    protected PDO $pdo;
    protected PDOStatement $stmt;

    // database properties
    protected array $tableSchema = [];
    protected array $triggerSchema = [];

    // query information
    protected array $results = [];

    // settings
    protected array $settingNames = [];


    /**
     * Default constructor
     *
     * @param string|null $host
     * @param string|null $user
     * @param string|null $pass
     * @param string|null $name
     */
    public function __construct(string $host = null, string $user = null, string $pass = null, string $name = null)
    {
        // start session if unstarted
        if (PHP_SESSION_ACTIVE !== session_status()) {
            session_start();
        }

        // dynamically define constant defaults
        if (!defined(constant_name: 'NESTBOX_DB_HOST')) define('NESTBOX_DB_HOST', 'localhost');
        if (!defined(constant_name: 'NESTBOX_DB_USER')) define('NESTBOX_DB_USER', 'root');
        if (!defined(constant_name: 'NESTBOX_DB_PASS')) define('NESTBOX_DB_PASS', '');
        if (!defined(constant_name: 'NESTBOX_DB_NAME')) define('NESTBOX_DB_NAME', '');

        // set default connection properties
        if (!$this->host = $host) {
            if (defined(constant_name: 'NESTBOX_DB_HOST')) {
                $this->host = constant(name: 'NESTBOX_DB_HOST');
            } else {
                throw new EmptyParamsException(message: "Missing database hostname.");
            }
        }

        if (!$this->user = $user) {
            if (defined(constant_name: 'NESTBOX_DB_USER')) {
                $this->user = constant(name: 'NESTBOX_DB_USER');
            } else {
                throw new EmptyParamsException(message: "Missing database username.");
            }
        }

        if (!$this->pass = $pass) {
            if (defined(constant_name: 'NESTBOX_DB_PASS')) {
                $this->pass = constant(name: 'NESTBOX_DB_PASS');
            } else {
                throw new EmptyParamsException(message: "Missing database password.");
            }
        }

        if (!$this->name = $name) {
            if (defined(constant_name: 'NESTBOX_DB_NAME')) {
                $this->name = constant(name: 'NESTBOX_DB_NAME');
            } else {
                throw new EmptyParamsException(message: "Missing database name.");
            }
        }

        $this->load_settings();
    }

    /**
     * Magic method to reset pdo connection details
     *
     * @param string|null $host
     * @param string|null $user
     * @param string|null $pass
     * @param string|null $name
     * @return void
     */
    public function __invoke(string $host = null, string $user = null, string $pass = null, string $name = null): void
    {
        // save settings
        $this->save_settings();

        // close any existing database connection
        $this->close();

        // reconnect to defined database
        $this->__construct($host, $user, $pass, $name);
    }

    /**
     * Default destructor
     */
    public function __destruct()
    {
        // save settings
        $this->save_settings();

        // close any existing database connection
        $this->close();
    }

    /*
        2.0 Connections
    */
    /**
     * Connect to the database, returns true on success, false on fail
     *
     * @return bool
     */
    protected function connect(): bool
    {
        // existing connection
        if ($this->check_connection()) return true;

        // MySQL Database
        try {
            $this->pdo = new PDO(
                "mysql:host={$this->host};dbname={$this->name}",
                $this->user,
                $this->pass,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_PERSISTENT => true,
                    PDO::ATTR_EMULATE_PREPARES => true, // off for :named placeholders
                    PDO::ATTR_STRINGIFY_FETCHES => false,
                ]
            );
        } catch (PDOException $e) {
            throw new NestboxException($e->getMessage());
        }

        // successful connection
        return true;
    }

    /**
     * Check if a connection exists
     *
     * @return bool
     */
    protected function check_connection(): bool
    {
        if (!empty($this->pdo)) {
            // test existing connection for timeout
            $this->prep("SELECT 1");
            $this->execute();
            $rows = $this->results();

            // check test results
            if (1 === $rows[0]['1']) return true;

            // kill dead connection
            $this->close();
        }

        return false;
    }

    /**
     * Close an existing connection
     */
    protected function close(): void
    {
        // https://www.php.net/manual/en/pdo.connections.php
        // "To close the connection, you need to destroy the object"
        $this->pdo = null;
    }

    /*
        3.0 Queries
    */

    /**
     * Execute a query against the database
     *
     * @param string $query
     * @param array $params
     * @param bool $close
     * @param bool $retry
     * @return bool
     */
    public function query_execute(string $query, array $params = [], bool $close = false, bool $retry = true): bool
    {
        // check query emptiness
        if (empty(trim($query))) {
            if ($close) {
                $this->close();
                return true;
            } else {
                throw new EmptyQueryException("Empty MySQL query provided.");
            }
        }

        // verify parameters
        $params = $this->verify_parameters($query, $params);

        // connect to database
        $this->connect();

        // prepare statement
        $this->prep($query, $params);

        // bind parameters
        if (!empty($params)) {
            foreach ($params as $var => $val) {
                $this->bind($var, $val);
            }
        }

        // execute
        try {
            if ($this->execute()) {
                if ($close) $this->close();
                return true;
            } else {
                if ($close) $this->close();
                return false;
            }
        } catch (InvalidTableException) {
            // if class tables haven't been created yet, try to create them and reattempt query execution
            if ($retry) {
                $this->create_class_tables();
                return $this->query_execute($query, $params, $close, retry: false);
            }

            throw new InvalidTableException();
        }
    }

    protected function create_class_tables(): void
    {
        foreach (get_class_methods($this) as $methodName) {
            if (str_starts_with(haystack: $methodName, needle: "create_class_table_")) {
                $this->$methodName();
            }
        }
    }

    /**
     * Verify/sanitize the parameters passed for a given query
     *
     * @param string $query
     * @param array $params
     * @return array
     */
    protected function verify_parameters(string $query, array $params): array
    {
        foreach ($params as $var => $val) {
            // remove unwanted parameters
            if (!strpos($query, ":{$var}")) {
                unset($params[$var]);
            }
        }
        return $params;
    }

    /**
     * Prepare a query into a statement
     *
     * @param string $query
     * @param array|null $params
     * @return bool
     */
    protected function prep(string $query, array $params = []): bool
    {
        if (empty($params)) {
            // prepare a statement
            $this->stmt = $this->pdo->prepare($query);
        } else {
            // prepare a statement with parameters
            $this->stmt = $this->pdo->prepare($query, $params);
        }
        return true;
    }

    /**
     * Bind a variable to a paramters
     *
     * @param $variable
     * @param $value
     * @return bool
     */
    protected function bind($variable, $value): bool
    {
        // set binding type
        $type = Nestbox::check_variable_type($value);
        if ("array" == $type) {
            throw new CannotBindArrayException("Cannot bind array type to :{$variable}");
        }

        // backwards compatibility or whatever
        $variable = (!str_starts_with($variable, ':')) ? ":{$variable}" : $variable;

        // bind value to parameter
        if (true === $this->stmt->bindValue($variable, $value, $type)) {
            return true;
        }

        // we didn't do it
        throw new FailedToBindValueException("Failed to bind '{$value}' to :{$variable} ({$type})");
    }

    /**
     * Checks variable type for parameter binding
     *
     * @param $var
     * @return int|string
     */
    protected static function check_variable_type($var): int|string
    {
        if (is_int($var)) return PDO::PARAM_INT;
        if (is_bool($var)) return PDO::PARAM_BOOL;
        if (is_null($var)) return PDO::PARAM_NULL;
        if (is_array($var)) return "array";
        return PDO::PARAM_STR;
    }

    /**
     * Execute a statement
     *
     * @return bool
     */
    protected function execute(): bool
    {
        // execute query
        try {
            if (!$this->stmt->execute()) {
                $error = $this->stmt->errorInfo();
                throw new QueryErrorException("MySQL error {$error[1]}: {$error[2]} ({$error[0]})");
            }
        } catch (PDOException $e) {
            $msg = $e->getMessage();
            throw new PDOException("PDO Exception: {$msg}");
        }
        return true;
    }

    /*
        4.0 Results
    */

    /**
     * Return resulting rows from a query; optionally, return only the first
     * row of data when only one row is expected
     *
     * @param bool $firstResultOnly
     * @return array
     */
    public function results(bool $firstResultOnly = false): array
    {
        // get result set
//        $rows = $this->stmt->fetchAll();
        return true === $firstResultOnly ? $this->stmt->fetchAll()[0] ?? [] : $this->stmt->fetchAll();
    }

    /**
     * Return the row count from the most recent query
     * @return int
     */
    public function row_count(): int
    {
        // get row count
        return $this->stmt->rowCount();
    }

    /**
     * Get the row ID of the last insert of the active connection
     *
     * @return string
     */
    public function last_insert_id(): string
    {
        return $this->pdo->lastInsertId();
    }

    /**
     * Return resulting rows as key pairs
     *
     * @return array
     */
    public function key_pair(): array
    {
        return $this->stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    /*
        5.0 Transactions
    */

    /**
     * Use a single query to perform an incremental transaction
     *
     * @param string $query
     * @param array $params
     * @param bool $commit
     * @param bool $close
     * @return mixed
     */
    public function transaction(string $query, array $params, bool $commit = false, bool $close = false): mixed
    {
        try {
            // start transaction if not already in progress
            if (!$this->pdo->inTransaction()) {
                $this->pdo->beginTransaction();
                if (!$this->pdo->inTransaction()) {
                    // couldn't start a transaction
                    throw new TransactionBeginFailedException("Failed to begin new transaction.");
                }
            }

            // perform single query for the transaction
            if ($this->execute($query, $params)) {
                $results = [
                    'rows' => $this->results(),
                    'row_count' => $this->row_count(),
                    'last_id' => $this->last_id(),
                ];
            }

            // commit the transaction and return any results
            if (true === $commit) {
                if ($this->pdo->commit()) {
                    // commit the transaction and return the results
                    return $results;
                } else {
                    throw new TransactionCommitFailedException("Failed to commit transaction.");
                }
            } else {
                // return the query results but leave transaction in progress
                return $results;
            }
        } catch (\Exception $e) {
            // oh no! roll back database and re-throw whatever fun error was encountered
            if (!$this->rollback()) {
                // we're really not having a good day today are we...
                throw new TransactionRollbackFailedException($e->getMessage() . " -- AND -- Failed to rollback database transaction.");
            }
            throw new TransactionException($e->getMessage());
        }
    }

    /**
     * Pass an array of SQL queries and perform a transaction with them
     *
     * @param array $queries
     * @return array
     */
    public function transaction_execute(array $queries): array
    {
        try {
            // connect to database
            $this->connect();

            // start transaction if not already in progress
            if ($this->pdo->inTransaction()) {
                throw new TransactionInProgressException("Unable to start new transaction while one is already in progress.");
            }
            $this->pdo->beginTransaction();

            // perform transaction
            $results = [];
            foreach ($queries as $query => $params) {
                // prepare query
                $this->prep($query, $params);

                // bind parameters
                if (!empty($params)) {
                    foreach ($params as $var => $val) {
                        $this->bind($var, $val);
                    }
                }

                if ($this->execute()) {
                    $results[] = [
                        'rows' => $this->results(),
                        'row_count' => $this->row_count(),
                        'last_id' => $this->last_id(),
                    ];
                }
            }

            // commit the transaction and return any results
            if ($this->pdo->commit()) {
                return $results;
            } else {
                throw new TransactionCommitFailedException("Failed to commit transaction.");
            }
        } catch (\Exception $e) {
            // Oh no, we dun goof'd! Roll back database and re-throw the error
            $this->pdo->rollback();
            throw new TransactionException($e->getMessage());
        }
    }

    public function rollback(): bool
    {
        if ($this->pdo->inTransaction()) {
            if ($this->pdo->rollback()) {
                return true;
            }
        }
        return false;
    }

    /*
        6.0 Quick Queries
    */
    /**
     * @param string $table
     * @param array $params
     * @param bool $update
     * @return int
     */
    public function insert(string $table, array $params, bool $update = true): int
    {
        // verify table
        if (!$this->valid_schema($table)) {
            throw new InvalidTableException("Cannot insert into invalid table: {$table}");
        }

        if (empty($params)) {
            throw new EmptyParamsException("Cannot insert no values into table.");
        }

        // verify columns
        foreach ($params as $col => $val) {
            if (!is_array($val)) {
                // inserting a single row
                if (!$this->valid_schema($table, $col)) {
                    throw new InvalidColumnException("Cannot insert into invalid column: {$table}.{$col}");
                }
                $insertMode = "single";
            } else {
                // inserting multiple rows
                foreach ($val as $c => $v) {
                    if (!$this->valid_schema($table, $c)) {
                        throw new InvalidColumnException("Cannot insert into invalid column: {$table}.{$col}");
                    }
                }
                $insertMode = "many";
            }
        }

        // prepare variables
        if ("single" == $insertMode) {
            $columns = array_keys($params);
            $cols = (count($params) > 1) ? implode("`,`", $columns) : current($columns);
            $vars = (count($params) > 1) ? implode(", :", $columns) : current($columns);
        } elseif ("many" == $insertMode) {
            $vars = [];
            $p = [];
            foreach ($params as $i => $row) {
                $columns = array_keys($row);
                $cols = (count($row) > 1) ? implode("`,`", $columns) : current($columns);
                $vars[] = (count($row) > 1) ? implode("_{$i}, :", $columns) . "_{$i}" : current($columns);
                foreach ($row as $c => $v) {
                    $p["{$c}_{$i}"] = $v;
                }
            }
            $vars = implode("),(:", $vars);
        }

        // create base query
        $query = "INSERT INTO `{$table}` (`{$cols}`) VALUES (:{$vars})";

        // add update
        if (true === $update) {
            $updates = [];
            $priKey = $this->table_primary_key($table);
            if ("single" == $insertMode) {
                foreach ($params as $col => $val) {
                    if ($col != $priKey) {
                        $updates[] = "`{$col}` = :{$col}";
                    }
                }
                $query .= " ON DUPLICATE KEY UPDATE " . implode(",", $updates);
            } elseif ("many" == $insertMode) {
                $updates = [];
                foreach ($params[0] as $col => $val) {
                    if ($col != $priKey) {
                        $updates[] = "`{$table}`.`{$col}` = `new`.`{$col}`";
                    }
                }
                $query .= " AS `new` ON DUPLICATE KEY UPDATE " . implode(",", $updates);
            }
        }

        $params = ("many" == $insertMode) ? $p : $params;

        // execute query
        if ($this->query_execute($query, $params)) {
            return $this->row_count();
        } else {
            return 0;
        }
    }

    /**
     * @param string $table
     * @param array $params
     * @param array $where
     * @param string $conjunction
     * @return int
     */
    public function update(string $table, array $params, array $where, string $conjunction = "AND"): int
    {
        // verify table
        if (!$this->valid_schema($table)) {
            throw new InvalidTableException("Cannot update invalid table: {$table}");
        }

        // parse updates
        $updates = [];
        foreach ($params as $col => $val) {
            if (!$this->valid_schema($table, $col)) {
                throw new InvalidColumnException("Cannot update invalid column: {$table}.{$col}");
            } else {
                $updates[] = "`{$col}` = :{$col}";
            }
        }
        $updates = implode(",", $updates);

        // define where keys
        $wheres = [];
        foreach ($where as $col => $val) {
            if ($this->valid_schema($table, $col)) {
                $wheres[] = "`{$col}` = :{$col}";
            }
        }
        $conjunction = (in_array(strtoupper($conjunction), ["AND", "OR"])) ? " " . strtoupper($conjunction) . " " : " AND ";
        $wheres = implode(" {$conjunction} ", $wheres);

        // compile query
        $query = "UPDATE `{$table}` SET {$updates} WHERE {$wheres};";

        // execute
        $this->query_execute($query, $params);
        return $this->row_count();
    }

    /**
     * @param string $table
     * @param array $where
     * @param $conjunction
     * @return int
     */
    public function delete(string $table, array $where, $conjunction = "AND"): int
    {
        // verify table
        if (!$this->valid_schema($table)) {
            throw new InvalidTableException("Cannot delete from invalid table: {$table}");
        }

        // parse columns
        $wheres = [];
        $params = [];
        foreach ($where as $col => $val) {
            if (!$this->valid_schema($table, $col)) {
                throw new InvalidColumnException("Cannot delete from invalid column: {$table}.{$col}");
            } else {
                $where[] = "`{$col}` = :{$col}";
                $params[$col] = $val;
            }
        }
        $conjunction = (in_array(strtoupper($conjunction), ["AND", "OR"])) ? " " . strtoupper($conjunction) . " " : " AND ";
        $wheres = implode(" {$conjunction} ", $wheres);

        // compile query
        $query = "DELETE FROM `{$table}` WHERE {$wheres};";

        // execute
        $this->query_execute($query, $params);
        return $this->row_count();
    }

    /**
     * @param string $table
     * @param array $where
     * @param string $conjunction
     * @return array
     */
    public function select(string $table, array $where = [], string $conjunction = "AND"): array
    {
        if (!$this->valid_schema($table)) {
            throw new InvalidTableException();
        }

        $wheres = [];
        $params = [];
        foreach ($where as $col => $val) {
            if (!$this->valid_schema($table, $col)) {
                throw new InvalidColumnException();
            } else {
                $wheres[] = "`{$col}` = {$col}";
                $params[$col] = $val;
            }
        }
        $conjunction = (in_array(strtoupper($conjunction), ["AND", "OR"])) ? " " . strtoupper($conjunction) . " " : " AND ";
        $wheres = implode($conjunction, $wheres);

        $sql = ($wheres)  ? "SELECT * FROM `{$table}` WHERE {$wheres};" : "SELECT * FROM `{$table}`;";
        $this->query_execute($sql, $params);
        return $this->results();
    }

    /*
        7.0 Schema
    */

    public function table_schema(): array
    {
        $this->load_table_schema();
        return $this->tableSchema;
    }

    /**
     * Load table schema
     *
     * @return bool
     */
    public function load_table_schema(): bool
    {
        $params = array();

        $sql = "SELECT `TABLE_NAME`,`COLUMN_NAME`,`DATA_TYPE`
                FROM `INFORMATION_SCHEMA`.`COLUMNS`
                WHERE `TABLE_SCHEMA` = :database_name;";

        if (!$this->query_execute($sql, ['database_name' => $this->name])) {
            return false;
        }

        foreach ($this->results() as $row) {
            $this->tableSchema[$row['TABLE_NAME']][$row['COLUMN_NAME']] = $row['DATA_TYPE'];
        }

        return true;
    }

    /**
     * Load trigger schema
     *
     * @return bool
     */
    public function load_trigger_schema(): bool
    {
        $sql = "SELECT `TRIGGER_NAME`, `EVENT_OBJECT_TABLE`
                FROM `INFORMATION_SCHEMA`.`TRIGGERS`
                WHERE `TRIGGER_SCHEMA` = '{$this->name}';";

        if (!$this->query_execute($sql, ['database_name' => $this->name])) return false;

        foreach ($this->results() as $row) {
            if (!in_array($row['EVENT_OBJECT_TABLE'], $this->triggerSchema)) {
                $this->triggerSchema[$row['EVENT_OBJECT_TABLE']] = [];
            }
            $this->triggerSchema[$row['EVENT_OBJECT_TABLE']][] = $row['TRIGGER_NAME'];
        }

        return true;
    }

    /**
     * Determine if a given table/column combination exists
     * within the database schema
     *
     * @param string $tbl
     * @param string|null $col
     * @return bool
     */
    public function valid_schema(string $table, string $column = null): bool
    {
        if (empty($this->tableSchema)) $this->load_table_schema();

        $table = trim($table ?? "");
        $col = trim($col ?? "");

        // check table
        if (!array_key_exists($table, $this->tableSchema)) {
            if (empty($this->tableSchema)) $this->load_table_schema();
            if (!array_key_exists($table, $this->tableSchema)) return false;
        }
        if (empty($col)) return true;

        // check column
        return array_key_exists($col, $this->tableSchema[$table]);
    }

    /**
     * Determine if a table exists within the database
     *
     * @param string $table
     * @return bool
     */
    public function valid_table(string $table): bool
    {
        return $this->valid_schema(table: $table);
    }

    /**
     * Determine if a column exists within a given table
     *
     * @param string $table
     * @param string $column
     * @return bool
     */
    public function valid_column(string $table, string $column): bool
    {
        return $this->valid_schema(table: $table, column: $column);
    }

    /**
     * Determine if a trigger exists within a given table
     *
     * @param string $table
     * @param string $trigger
     * @return bool
     */
    public function valid_trigger(string $table, string $trigger): bool
    {
        if (empty($this->triggerSchema)) {
            $this->load_trigger_schema();
        }

        if (!$this->valid_schema(table: $table)) return false;

        if (in_array(needle: $trigger, haystack: $this->triggerSchema[$table] ?? [])) return true;

        // reload in case schema has changed since last load
        $this->load_trigger_schema();
        return in_array(needle: $trigger, haystack: $this->triggerSchema[$table] ?? []);
    }

    /**
     * Get primary key for a given table within the database
     *
     * @param string $table
     * @return string
     */
    public function table_primary_key(string $table): string
    {
        if (!$this->valid_schema($table)) {
            throw new InvalidTableException(message: "Cannot get primary key of invalid table: {$table}");
        }

        $sql = "SHOW KEYS FROM `{$table}` WHERE `Key_name` = 'PRIMARY';";
        if ($this->query_execute($sql)) {
            $rows = $this->results(firstResultOnly: true);
            return $rows["Column_name"];
        } else {
            return "";
        }
    }

    /*
     *  8.0 Nestbox Settings
     */
    protected function create_class_table_nestbox_settings(): bool
    {
        $sql = "CREATE TABLE IF NOT EXISTS `nestbox_settings` (
                    `package_name` VARCHAR( 64 ) NOT NULL ,
                    `setting_name` VARCHAR( 64 ) NOT NULL ,
                    `setting_type` VARCHAR( 64 ) NOT NULL ,
                    `setting_value` VARCHAR( 128 ) NULL ,
                    PRIMARY KEY ( `setting_name` )
                ) ENGINE = InnoDB DEFAULT CHARSET=UTF8MB4 COLLATE=utf8_unicode_ci;";

        return $this->query_execute($sql);
    }

    public function load_settings(): array
    {
        $where = ['package_name' => self::PACKAGE_NAME];

        $settings = $this->parse_settings($this->select(table: 'nestbox_settings', where: $where));

        foreach ($settings as $name => $value) {
            if (property_exists($this, $name)) {
                $this->update_setting($name, $value);
            }
        }

        return $settings;
    }

    public function update_setting(string $name, string $value): bool
    {
        if (property_exists($this, $name)) {
            $this->$name = $value;
            return true;
        }

        return false;
    }

    public function save_settings(): void
    {
        $sql = "INSERT INTO `nestbox_settings` (
                    `package_name`, `setting_name`, `setting_type`, `setting_value`
                ) VALUES (
                    :package_name, :setting_name, :setting_type, :setting_value
                ) ON DUPLICATE KEY UPDATE
                    `package_name` = :package_name,
                    `setting_name` = :setting_name,
                    `setting_type` = :setting_type,
                    `setting_value` = :setting_value;";

        foreach (get_class_vars(get_class($this)) as $setting) {
            if (!str_starts_with($setting, needle: self::PACKAGE_NAME)) {
                continue;
            }

            $params = [
                "package_name" => self::PACKAGE_NAME,
                "setting_name" => $setting,
                "setting_type" => $this->parse_setting_type($this->$setting),
                "setting_value" => strval($this->$setting),
            ];

            $this->query_execute($sql, $params);
        }
    }

    protected function parse_settings(array $settings): array
    {
        $output = [];
        foreach ($settings as $setting) {
            $output[$setting['setting_name']] = $this->setting_type_conversion(type: $setting['setting_type'], value: $setting['setting_value']);
        }
        return $output;
    }

    protected function parse_setting_type(int | float | bool | array | string $setting): string
    {
        if (is_int($setting)) return "string";
        if (is_float($setting)) return "float";
        if (is_bool($setting)) return "boolean";
        if (is_array($setting)) return "array";
        if (json_validate($setting)) return "json";
        return "string";
    }

    protected function setting_type_conversion(string $type, string $value): int | float | bool | array | string
    {
        if ("int" == strtolower($type)) {
            return intval($value);
        }

        if (in_array(strtolower($type), ["double", "float"])) {
            return floatval($value);
        }

        if ("bool" == strtolower($type)) {
            return boolval($value);
        }

        if (in_array(strtolower($type), ["array", "json"])) {
            return json_decode($value, associative: true);
        }

        return $value;
    }

    /*
     *  9.0 Error Logging
     */
    protected function create_class_table_nestbox_errors(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS `nestbox_errors` (
                    `error_id` INT NOT NULL AUTO_INCREMENT ,
                    `occurred` NOT NULL DEFAULT CURRENT_TIMESTAMP ,
                    `message` VARCHAR( 512 ) NOT NULL ,
                    `query` VARCHAR( 4096 ) NOT NULL ,
                    PRIMARY KEY ( `error_id` )
                ) ENGINE = InnoDB DEFAULT CHARSET=UTF8MB4 COLLATE=utf8_unicode_ci;";
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

    /*
     *  10.0 Database Imports & Exports
     */

    public function dump_table(string $table): array
    {
        return $this->select($table);
    }

    public function dump_database(array $tables = []): array
    {
        if (empty($tables)) {
            $tables = array_keys($this->table_schema());
        }

        $output = [];

        foreach ($tables as $table) {
            $output[$table] = $this->dump_table(table: $table);
        }

        return $output;
    }

    public function load_table(string $table, string | array $data): int
    {
        $updateCount = 0;

        if (!$this->valid_schema($table)) {
            return $updateCount;
        }

        if (is_string($data)) {

            if (!json_validate($data)) {
                throw new NestboxException(message: "Invald or malformed JSON string provided.");
            }

            $data = json_decode($data, associative: true);
        }

        $data = json_decode($data, associative: true);

        foreach ($data as $row) {
            $updateCount += $this->insert(table: $table, params: $row, update: true);
        }

        return $updateCount;
    }

    public function load_database(string | array $input): int
    {
        $updateCount = 0;

        if (is_string($input)) {

            if (!json_validate($input)) {
                throw new NestboxException(message: "Invald or malformed JSON string provided.");
            }

            $input = json_decode($input, associative: true);
        }

        foreach ($input as $table => $data) {
            $updateCount += $this->load_table(table: $table, data: $data);
        }

        return $updateCount;
    }

    /*
        Other
    */
    /**
     * Generate HTML code for a two-dimensional array
     *
     * @param string $table
     * @param string $tableClass
     * @param array $columnClass
     * @return string
     */
    public static function html_table(array $table, string $tableClass = "", array $columnClass = []): string
    {
        // table start
        $code = "";
        $code .= "<table class='{$tableClass}'>";

        // add headers
        $hdrs = "";
        foreach ($table[0] as $col => $data) {
            $class = (array_key_exists($col, $columnClass)) ? "class='{$columnClass[$col]}'" : "";
            $hdrs .= "<th {$class}>{$col}</th>";
        }
        $code .= "<tr>{$hdrs}</tr>";

        // add data
        foreach ($table as $tblRow) {
            $row = "";
            foreach ($tblRow as $col => $val) {
                $class = (array_key_exists($col, $columnClass)) ? "class='{$columnClass[$col]}'" : "";
                $row .= "<td {$class}>{$val}</td>";
            }
            $code .= "<tr>{$row}</tr>";
        }

        // table end
        $code .= "</table>";
        return $code;
    }

    public static function validate_nonempty_params(array $params, int $minimum = 1): bool
    {
        $empty = [];
        foreach ($params as $param => $value) {
            if (isset($param)) {
                if (!empty(trim("{$value}"))) continue;
            }
            $empty[] = $param;
        }
        if (0 < count($empty)) {
            $keys = implode(", ", $empty);
            throw new EmptyParamsException("Missing or empty parameters: {$keys}");
        }
        return true;
    }

    private function last_id(): int | string | null
    {
        return null;
    }
}
