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
use Supergnaw\Nestbox\Exception\InvalidSchemaSyntaxException;
use Supergnaw\Nestbox\Exception\InvalidTableException;
use Supergnaw\Nestbox\Exception\EmptyParamsException;
use Supergnaw\Nestbox\Exception\MissingDatabaseHostException;
use Supergnaw\Nestbox\Exception\MissingDatabaseNameException;
use Supergnaw\Nestbox\Exception\MissingDatabasePassException;
use Supergnaw\Nestbox\Exception\MissingDatabaseUserException;
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

    // connection properties
    protected string $host = 'localhost';
    protected string $user = 'root';
    protected string $pass = '';
    protected string $name = '';

    // query information
    protected array $results = [];

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
        if (PHP_SESSION_ACTIVE !== session_status()) session_start();

        $this->host = $host;
        $this->user = $user;
        $this->pass = $pass;
        $this->name = $name;

        // default overrides with defined environment constants
        if (!$host && defined(constant_name: 'NESTBOX_DB_HOST')) $this->host = constant(name: 'NESTBOX_DB_HOST');
        if (!$user && defined(constant_name: 'NESTBOX_DB_USER')) $this->user = constant(name: 'NESTBOX_DB_USER');
        if (!$pass && defined(constant_name: 'NESTBOX_DB_PASS')) $this->pass = constant(name: 'NESTBOX_DB_PASS');
        if (!$name && defined(constant_name: 'NESTBOX_DB_NAME')) $this->name = constant(name: 'NESTBOX_DB_NAME');

        // manual overrides for new or invoked instantiations
        if (!$this->host) throw new MissingDatabaseHostException();
        if (!$this->user) throw new MissingDatabaseUserException();
        if (!$this->pass) throw new MissingDatabasePassException();
        if (!$this->name) throw new MissingDatabaseNameException();

        // make sure class tables have been created
        $this->check_class_tables();

        // load settings
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

    // Input Validation
    use InputValidationTrait;

    // Class Tables Generation
    use ClassTablesTrait;

    // Database Connections
    use ConnectionsTrait;

    // Query Execution
    use QueryExecutionTrait;

    // Query Results
    use QueryResultsTrait;

    // Transactions
    use TransactionsTrait;

    // Quick Queries
    use QuickQueriesTrait;

    // Table Manipulations
    use TableManipulationsTrait;

    // Schema Parsing
    use SchemaTrait;

    // Nestbox Settings
    use SettingsTrait;

    // Error Logging
    use ErrorLoggingTrait;

    // Database Imports & Exports
    use DatabaseImportExportTrait;

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
}
