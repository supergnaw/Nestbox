<?php

// this is the earliest version of the origin of this project I could find

/* ### Linchpin: A PDO Databse Wrapper ###
 *	Developer:	Supergnaw & lots of Googlefu
 *				Any extra functions are externally
 *				referenced by that function's definition
 *	Version:	5.3.1
 *	Date:		2016-03-31
 *
 * ### Helpful Resources ###
 *	The following resource helped in the creation of this class:
 *	http://culttt.com/2012/10/01/roll-your-own-pdo-php-class/
 *	http://code.tutsplus.com/tutorials/why-you-should-be-using-phps-pdo-for-database-access--net-12059
 *
 * ### SQL Executor ###
 *
 *
 * ### Query Builders ###
 *	These are a set of functions that build queries from user-defined
 *	inputs. They work well enough for simple queries but do not handle
 *	things like duplicate columns and multiple types of joins in the
 *	same query very well
 *
 *	fetch_table ()
 *	insert_row ()
 *	update_row ()
 *	delete_row ()
 *
 * ### Transactions ###
 *
 *
 *
 * ### Things To Do ###
 * - expand insert/update/delete where parameters beyond a=b
 * - fix the where/filter implode to avoid single incorrect columns fucking up array to string conversions
 *
 */

class linchpin {
    // Class Variables
    public $dbh;	// database handler
    public $stmt;	// query statement holder
    public $lastErr;// last error in the class
    public $err;	// error log array
    public $debug;	// debug log array

    ##	1.0 Structs
    //	  ____  _                   _
    //	 / ___|| |_ _ __ _   _  ___| |_ ___
    //	 \___ \| __| '__| | | |/ __| __/ __|
    //	  ___) | |_| |  | |_| | (__| |_\__ \
    //	 |____/ \__|_|   \__,_|\___|\__|___/

    // Default constructor
    public function __construct($host = 'localhost', $user = 'root', $pass = '', $name = '', $dir = '', $type = 'mysql') {
        // set class vars
        $this->set_vars($host, $user, $pass, $name, $dir, $type);
    }
    // Default destructor
    public function __destruct() {
        // close any existing database connection
        $this->close();
    }
    // Set class vairable defaults then connect
    public function set_vars($host = "localhost", $user = "root", $pass = "", $name = "", $dir = "", $type = "mysql") {
        // set the class variables, use defined constants or passed variables
        $this->dbHost = $host;
        $this->dbUser = $user;
        $this->dbPass = $pass;
        $this->dbName = $name;
        $this->dbDir = $dir;
        $this->dbType = $type;

        // enable debug dump
        $this->logDebug = true;

        // delete existing connection
        if (!is_null($this->dbh)) $this->dbh = null;
    }

    ##	2.0 Connections
    //	   ____                            _   _
    //	  / ___|___  _ __  _ __   ___  ___| |_(_) ___  _ __  ___
    //	 | |   / _ \| '_ \| '_ \ / _ \/ __| __| |/ _ \| '_ \/ __|
    //	 | |__| (_) | | | | | | |  __/ (__| |_| | (_) | | | \__ \
    //	  \____\___/|_| |_|_| |_|\___|\___|\__|_|\___/|_| |_|___/

    // Connect to database
    public function connect () {
        // check for existing connection
        if ( true === $this->check_connection ()) return true;

        // try to connect to database
        try {
            switch ( $this->dbType ) {
                case 'mssql':	// MS Sql Server
                    $this->dbh = new PDO("mssql:host=". $this->dbHost .";dbname=". $this->dbName .", ". $this->dbUser .", ". $this->dbPass);
                    break;
                case 'sybase':	// Sybase with PDO_DBLIB
                    $this->dbh = new PDO("sybase:host=". $this->dbHost .";dbname=". $this->dbName .", ". $this->dbUser .", ". $this->dbPass);
                    break;
                case 'sqlite':	// SQLite
                    $this->dbh = new PDO("sqlite:". DB_DIR . DIRECTORY_SEPARATOR . $this->dbName);
                    break;
                case 'mysql':	// Mysql
                    $this->dbh = new PDO("mysql:host=". $this->dbHost .";dbname=". $this->dbName, $this->dbUser, $this->dbPass);
                    break;
            }
        } catch ( PDOException $err ) {
            // catch error
            $this->err[] = "Error!: " . $err->getMessage();
            return false;
        }

        // connection successful
        return true;
    }
    // Test connection
    public function check_connection () {
        // check if connected already
        if ( !empty ( $this->dbh )) {
            // check if connection is still available
            $res = $this->sql_exec ( 'SELECT 1', null, null, false );
            if ( 1 === $res[0]['1'] ) {
                return true;
            } else {
                // reset dead connection
                $this->close();
                return false;
            }
        } else {
            return false;
        }
    }
    // Disconnect from database
    public function close() {
        $this->dbh = null;		// secure
        unset ( $this->dbh );	// super secure
    }

    ##	3.0 Statement Execution
    //	  ____  _        _                            _     _____                     _   _
    //	 / ___|| |_ __ _| |_ ___ _ __ ___   ___ _ __ | |_  | ____|_  _____  ___ _   _| |_(_) ___  _ __
    //	 \___ \| __/ _` | __/ _ \ '_ ` _ \ / _ \ '_ \| __| |  _| \ \/ / _ \/ __| | | | __| |/ _ \| '_ \
    //	  ___) | || (_| | ||  __/ | | | | |  __/ | | | |_  | |___ >  <  __/ (__| |_| | |_| | (_) | | | |
    //	 |____/ \__\__,_|\__\___|_| |_| |_|\___|_| |_|\__| |_____/_/\_\___|\___|\__,_|\__|_|\___/|_| |_|

    // Execute query with optional parameters
    public function sql_exec ( $query, $params = null, $close = false, $connect = true ) {
        // connect to database
        if ( true == $connect ) if (!$this->connect()) return false;

        // debug
        if ($this->logDebug) $this->debug[] = "Connection created.";

        // prepare statement
        $this->prep($query);

        // debug
        if ($this->logDebug) $this->debug[] = "Statement prepared: {$this->stmt->queryString}";

        // bind parameters
        if (!empty($params) && is_array($params)) {
            foreach ($params as $name => $value) {
                if (!$this->bind($name, $value)) $this->err[] = "Could not bind {$value} to {$name}.";

                // debug
                if ($this->logDebug) $this->debug[] = "Paramater bound: '{$value}' to `{$name}`";
            }
        }

        // execute & return
        if ( $this->execute()) {
            // debug
            if ($this->logDebug) $this->debug[] = "Statement successfully executed.";

            // return results of query based on statement type
            $string = str_replace(array("\n","\t"), array(" ",""), $query);
            $type = trim(strtolower(strstr($string, ' ', true)));
            switch ($type) {
                case 'select':	// return all resulting rows
                case 'show':
                    if ($this->logDebug) $this->debug[] = "Return results.";
                    $return = $this->results();
                    break;
                case 'insert':	// return number of rows affected
                case 'update':
                case 'delete':
                    $count = $this->row_count();
                    if ($this->logDebug) $this->debug[] = "Return number of rows affected: {$count}";
                    $return = $count;
                    break;
                default:		// i don't know what you want from me but it worked
                    if ($this->logDebug) $this->debug[] = "No Case For Switch: {$type}";
                    $return = true;
                    break;
            }
        } else {
            $return = false;
        }

        // close the connection if requested
        if ( true == $close ) $this->close ();

        // return query results
        return $return;
    }
    // Prepare a statement query
    public function prep ( $query ) {
        $this->stmt = $this->dbh->prepare($query);
    }
    // Bind query parameters
    public function bind ( $name, $value, $type = null, $table = null ) {
        // get value type if not set
        if (empty($type)) {
            if (!empty($table)) {
                $type = $this->col_datatype($name, $table);
            } else {
                switch ( true ) {
                    case is_int ( $value ):		// integer
                        $type = PDO::PARAM_INT;
                        break;
                    case is_bool ( $value ):	// boolean
                        $type = PDO::PARAM_BOOL;
                        break;
                    case is_null ( $value ):	// null
                        $type = PDO::PARAM_NULL;
                        break;
                    default:					// string
                        $type = PDO::PARAM_STR;
                }/**/
            }
        }

        // backwards compatibility; older versions require colon prefix where newer versions do not
        if ( ':' != substr ( $name, 0, 1 )) $name = ':' . $name;

        // bind parameter to statement
        return $this->stmt->bindValue ( $name, $value, $type );
    }
    // Execute a prepared statement
    public function execute () {
        // execute query
        if ( !$this->stmt->execute ()) {
            // get error info
            $error = $this->stmt->errorInfo();

            //$this->err[] = "Statement: " . $this->stmt;
            // error logging
            $this->err[] = $error[2] . " (MySQL error " . $error[1] . ")";
            if ($this->logDebug) $this->debug[] = $this->stmt->errorInfo();

            // save error number
            $this->lastErr = $error[1];

            // failed to execute
            return false;
        } else {
            return true;
        }
    }
    // Return associated array
    public function results() {
        return $this->stmt->fetchAll ( PDO::FETCH_ASSOC );
    }
    // Get the number of rows affected by the last query
    public function row_count() {
        return $this->stmt->rowCount();
    }
    // I forgot what function to use
    public function exec_sql ( $query, $params = null ) {
        return $this->sql_exec ( $query, $params );
    }
    // Notepad++ likes this version the most for autocomplete
    public function sqlexec ( $query, $params = null ) {
        return $this->sql_exec ( $query, $params );
    }

    ##	4.0 Transactions
    //	  _____                               _   _
    //	 |_   _| __ __ _ _ __  ___  __ _  ___| |_(_) ___  _ __  ___
    //	   | || '__/ _` | '_ \/ __|/ _` |/ __| __| |/ _ \| '_ \/ __|
    //	   | || | | (_| | | | \__ \ (_| | (__| |_| | (_) | | | \__ \
    //	   |_||_|  \__,_|_| |_|___/\__,_|\___|\__|_|\___/|_| |_|___/

    // Begin a transaction
    public function trans_begin() {
        return $this->dbh->beginTransaction();
    }
    // End a transaction and commit changes
    public function trans_end() { // add functionality for sqlite - sleep for a few seconds then try again
        return $this->dbh->commit();
    }
    // Cancel a transaction and roll back changes
    public function trans_cancel() {
        return $this->dbh->rollBack();
    }
    // Check if transaction is currently active
    public function trans_active() {
        return $this->dbh->inTransaction();
    }
    // Perform a transaction of queries
    public function trans_exec ( $queries ) {
        // verify connection
        $this->connect();

        // check if queries is an array
        if ($this->logDebug) $this->debug[] = "Check formatting if passed transaction queries.";
        if (!is_array($queries)) {
            $this->err[] = "Warning: transactions must be an array of queries.";
            return false;
        }

        // verify no active transactions
        if ($this->logDebug) $this->debug[] = "Check no transaction is currently active.";
        if (true == $this->trans_active()) {
            $this->err[] = "Warning: transaction is currently active.";
            return false;
        }

        // start the transaction
        if ($this->logDebug) $this->debug[] = "Begin new transaction.";
        if (!$this->trans_begin()) {
            $this->err[] = "Error: could not begin transaction.";
            return false;
        }

        // loop through each query
        foreach ($queries as $query => $params) {
            // execute each query
            $res[] = $this->sql_exec($query, $params, false);
        }

        // end/commit transaction
        if (!$this->trans_end()) {
            $this->err[] = "Error: could not commit changes.";
            if (!$this->trans_cancel()) { // rollback on failure
                $this->err[] = "Error: failed to rollback the transaction.";
                return false;
            } else {
                $this->err[] = "Transaction rolled back successfully.";
            }
        } else {
            return $res;
        }
    }

    ##	5.0 Schema
    //	  ____       _
    //	 / ___|  ___| |__   ___ _ __ ___   __ _
    //	 \___ \ / __| '_ \ / _ \ '_ ` _ \ / _` |
    //	  ___) | (__| | | |  __/ | | | | | (_| |
    //	 |____/ \___|_| |_|\___|_| |_| |_|\__,_|

    // Validate a table is in the database
    public function valid_table ( $table, $reload = false ) {
        // set persistant table variable between function calls
        static $tables = array();

        // check if database tables needs to be reloaded
        if (empty($tables) || true == $reload) {
            // debug
            if ($this->logDebug) $this->debug[] = "Querying for tables.";

            // clear for new table
            $tables = array();

            $res = $this->sql_exec("SHOW TABLES");
            foreach ($res as $key => $tbl) $tables[] = current($tbl);
        }

        // check if table is present in database
        if (in_array($table, $tables)) {
            return true;
        } else {
            $this->err[] = "Warning: invalid table provided {$table}";
            return false;
        }
    }
    // Validate a column is in a table
    public function valid_column( $table, $column, $reload = false ) {
        // set persistence
        static $tbl = '';
        static $cols = array();

        // update table and columns
        if ($tbl != $table) {
            $tbl = $table;
            $cols = array();
            $res = $this->column_types($tbl);

            if (false != $res) {
                foreach ($res as $col) {
                    $cols[] = $col['Field'];
                }
            } else {
                return false;
            }
        }

        // verify column is valid
        if (in_array($column, $cols)) {
            return true;
        } else {
            return false;
        }
    }
    // Gets table columns and attributes
    public function column_types( $table, $column = null ) {
        // verify table exists
        if (false == $this->valid_table($table)) return false;

        // get table columns
        $results = $this->sql_exec("SHOW COLUMNS IN `{$table}`", null, $table);

        // return results
        return $results;
    }
    // get datatype of column
    public function col_datatype( $column, $table ) {
        // verify table and column
        if (false == $this->valid_column($table, $column)) return false;

        // initialize static variables
        static $tbl = '';
        static $colData = array();

        // update table data
        if (empty($tbl) || $tbl != $table) {
            $tbl = $table;
            unset($colData);
            $cols = $this->column_types($tbl);
            foreach ($cols as $key => $col) {
                list($cols[$key]['Type']) = explode("(", $cols[$key]['Type']);
                $colData[$col['Field']] = $col;
            }
        }

        // default datatype arrays
        return $colData[$column]['Type'];
    }
    // Gets an array of columns for a table
    public function get_columns ( $table ) {
        // get calumn data
        $results = $this->column_types($table);

        // return false on error or empty
        if (false == $results) return false;

        // var prep
        $columns = array();
        // parse columns
        foreach ($results as $result) $columns[] = $result['Field'];
        // return data
        return $columns;
    }

    ##	6.0 Table Display
    //	  _____     _     _        ____  _           _
    //	 |_   _|_ _| |__ | | ___  |  _ \(_)___ _ __ | | __ _ _   _
    //	   | |/ _` | '_ \| |/ _ \ | | | | / __| '_ \| |/ _` | | | |
    //	   | | (_| | |_) | |  __/ | |_| | \__ \ |_) | | (_| | |_| |
    //	   |_|\__,_|_.__/|_|\___| |____/|_|___/ .__/|_|\__,_|\__, |
    //										  |_|            |___/

    // Display two dimensional array or specified table as an ascii-styled table
    public function ascii_table($table, $textFormat = array(), $borders = 2) {
        // data check
        if (!is_array($table)) {
            if (false == $this->valid_table($table)) {
                $sql = "SELECT * FROM `{$table}`";
                $table = $this->sql_exec($sql);
            } else {
                return false;
            }
        }

        // get column widths
        $headers = array_keys(current($table));	// get column names
        $length = array();						// set lengths for each item
        foreach ($headers as $header) $length[$header] = strlen(strip_tags($header));
        foreach ($table as $tr => $row) {
            // get max length of all items
            foreach ($row as $col => $item) {
                // strip html tags (invisible so would mess up widths)
                $item = strip_tags($item);
                // format numbers as needed
                if (array_key_exists($col, $textFormat)) $table[$tr][$col] = $this->num_format($item, $textFormat[$col]);
                // adds padding offsets for fomatting as needed
                $offsets = array('money' => 2, '$' => 2, 'percent' => 2, '%' => 2);
                $offset = (array_key_exists($col, $textFormat)
                    && array_key_exists($textFormat[$col], $offsets))
                    ? $offsets[$textFormat[$col]]
                    : 0;
                // compare
                $length[$col] = max($length[$col], strlen($item) + $offset);
            }
        }

        // aesthetic correction for header centering
        foreach ($length as $item => $len) if ((strlen($item) % 2) != ($len % 2)) $length[$item] = $len + 1;

        // create divider
        $div = "+";
        $interval = ($borders > 1) ? "--+" : "---";	// h & z junction
        $vert = ($borders > 0) ? "|" : " ";			// vertical dividers
        foreach ($length as $header => $len) $div .= (str_repeat("-", $len)) . $interval;
        if ($borders > 0) $code[] = $div;			// add divider as long as borders included

        // add column headers
        $row = "";
        foreach ($headers as $header) {
            // $row .= "| " . strtoupper($header) . (str_repeat(" ", $length[$header] - strlen($header))) . " ";
            $row .= "{$vert} " . $this->ascii_format(strtoupper($header), $length[$header], 'center') . " ";
        }
        $code[] = "{$row}{$vert}";
        if ($borders > 1) $code[] = $div;

        // add each item
        foreach ($table as $row) {
            // begin row
            $line = "";
            foreach ($row as $key => $item) {
                // add item to row with appropriate padding
                $align = (array_key_exists($key, $textFormat)) ? $textFormat[$key] : 'left';
                $line .= "{$vert} " . $this->ascii_format($item, $length[$key], $align) . " ";
            }
            // add row and divider
            $code[] = "{$line}{$vert}";
            if ($borders > 2) $code[] = $div;
        }

        // bottom border
        if ($borders == 2 || $borders == 1) $code[] = $div;

        // implode and print
        $code = implode("\n", $code);
        echo "<pre>{$code}</pre>";
    }
    // Add whitespace padding to a string
    public function ascii_format($html, $length = 0, $format = "left") {
        $text = preg_replace("/<[^>]*>/", "", $html);
        if (is_numeric($length) && $length > strlen($text)) {
            // get proper length
            $length = max($length, strlen($text));
            switch ($format) {
                case 'right':
                case 'r':
                    $text = str_repeat(" ", $length - strlen($text)) . $html;
                    break;
                case 'center':
                case 'c':
                    $temp = $length - strlen($text);
                    $left = floor($temp / 2);
                    $right = ceil($temp / 2);
                    $text = str_repeat(" ", $left) . $html . str_repeat(" ", $right);
                    break;
                case 'money':
                case '$':
                    $text = (is_numeric($text)) ? number_format($text, 2) : $text;
                    $padd = $length - strlen($text) - 2;
                    $text = "$ " . str_repeat(" ", $padd) . $text;
                    break;
                case 'percent':
                case '%';
                    $padd = $length - strlen($text) - 2;
                    $text = str_repeat(" ", $padd) . $text . " %";
                    break;
                case 'left':
                case 'l':
                default:
                    $temp = $length - strlen($text);
                    $text = $html . str_repeat(" ", $temp);
                    break;
            }
        }
        return $text;
    }
    // Formats a number according to the specified format
    public function num_format($num, $format) {
        switch ($format) {
            case 'money':
            case '$':
                $num = (is_numeric($num)) ? number_format($num, 2) : $num;
                break;
            case 'percent':
            case '%':
                $num = (is_numeric($num)) ? number_format($num, 3) : $num;
                break;
        }

        return $num;
    }
    // Display two dimensional array or specified table as an HTML table
    public function html_table($table, $class = null, $altHeaders = array(), $caption = null) {
        // data check
        if (!is_array($table)) {
            if (false == $this->valid_table($table)) {
                $sql = "SELECT * FROM `{$table}`";
                $table = $this->sql_exec($sql);
            } else {
                return false;
            }
        }

        // begin table code
        $code = array();
        $code[] = (empty($class)) ? "<table>" : "<table class = '{$class}'>";
        if (!empty($caption)) $code[] = "	<caption>{$caption}</caption>";

        // start table headers
        $headers = array_keys(current($table));
        foreach ($headers as $key => $header) if (array_key_exists($header, $altHeaders)) $headers[$key] = $altHeaders[$header];
        $code[] = "	<tr><th>" . implode("</th><th>", $headers) . "</th></tr>";

        // include tabular data
        foreach ($table as $row) $code[] = "		<tr><td>" . implode("</td><td>", $row) . "</td></tr>";

        // end table code
        $code[] = "</table>";

        // finalize and return
        $code = implode("\n", $code);
        return $code;
    }

    ##	7.0 Query Builders
    //	   ___                          ____        _ _     _
    //	  / _ \ _   _  ___ _ __ _   _  | __ ) _   _(_) | __| | ___ _ __ ___
    //	 | | | | | | |/ _ \ '__| | | | |  _ \| | | | | |/ _` |/ _ \ '__/ __|
    //	 | |_| | |_| |  __/ |  | |_| | | |_) | |_| | | | (_| |  __/ |  \__ \
    //	  \__\_\\__,_|\___|_|   \__, | |____/ \__,_|_|_|\__,_|\___|_|  |___/
    //							|___/

    // Simple select for one table or many tables with joins and sorts with minimal optional parameters
    public function fetch_table ( $table, $where = null, $filter = null, $group = null, $limit = null, $join = null, $count = null ) {
        // initiate variables
        $params = array ();
        $sql = '';
        $wheres = array ();
        $filters = array ();
        $groups = array ();

        // where
        if ( is_array ( $where )) {
            foreach ( $where as $col => $val ) {
                // validate column is real
                if ( is_array ( $table )) {
                    foreach ( $table as $tbl => $join ) { // for an array of tables
                        if ( true == $this->valid_column ( $tbl, $col )) {
                            if ( 'IS NULL' == strtoupper ( trim ( $val ))) {
                                $temp = "`{$col}` IS NULL";
                                if ( !in_array ( $temp, $wheres )) $wheres[] = $temp;
                            } elseif ( 'IS NOT NULL' == strtoupper ( trim ( $val ))) {
                                $temp = "`{$col}` IS NOT NULL";
                                if ( !in_array ( $temp, $wheres )) $wheres[] = $temp;
                            } elseif ( 'LIKE' == strtoupper ( substr ( trim ( $val ), 0, 4 ))) {
                                $temp = "`{$col}` LIKE :{$col}";
                                if ( !in_array ( $temp, $wheres )) {
                                    $wheres[] = $temp;
                                    $col = $this->increment_keys ( $col, $params );
                                    $params[$col] = trim ( str_replace ( 'LIKE', '', $val ));
                                }
                            } elseif ( '>=' == substr ( $val, 0, 2 )) {
                                $temp = "`{$col}` >= :{$col}";
                                if ( !in_array ( $temp, $wheres )) {
                                    $wheres[] = $temp;
                                    $col = $this->increment_keys ( $col, $params );
                                    $params[$col] = trim ( str_replace ( '>=', '', $val ));
                                }
                            } elseif ( '>' == substr ( $val, 0, 1 )) {
                                $temp = "`{$col}` > :{$col}";
                                if ( !in_array ( $temp, $wheres )) {
                                    $wheres[] = $temp;
                                    $col = $this->increment_keys ( $col, $params );
                                    $params[$col] = trim ( str_replace ( '>', '', $val ));
                                }
                            } elseif ( '<=' == substr ( $val, 0, 2 )) {
                                $temp = "`{$col}` <= :{$col}";
                                if ( !in_array ( $temp, $wheres )) {
                                    $wheres[] = $temp;
                                    $col = $this->increment_keys ( $col, $params );
                                    $params[$col] = trim ( str_replace ( '<=', '', $val ));
                                }
                            } elseif ( '<' == substr ( $val, 0, 1 )) {
                                $temp = "`{$col}` < :{$col}";
                                if ( !in_array ( $temp, $wheres )) {
                                    $wheres[] = $temp;
                                    $col = $this->increment_keys ( $col, $params );
                                    $params[$col] = trim ( str_replace ( '<', '', $val ));
                                }
                            } elseif ( '!=' == substr ( $val, 0, 2 )) {
                                $temp = "`{$col}` != :{$col}";
                                if ( !in_array ( $temp, $wheres )) {
                                    $wheres[] = $temp;
                                    $col = $this->increment_keys ( $col, $params );
                                    $params[$col] = trim ( str_replace ( '!=', '', $val ));
                                }
                            } else {
                                $temp = "`{$col}` = :{$col}";
                                if ( !in_array ( $temp, $wheres )) {
                                    $wheres[] = $temp;
                                    $col = $this->increment_keys ( $col, $params );
                                    $params[$col] = $val;
                                }
                            }
                        }
                    }
                } else { // for a single table
                    if ( true == $this->valid_column ( $table, $col )) {
                        $temp = "`{$col}` = :{$col}";
                        if ( !in_array ( $temp, $wheres )) {
                            $wheres[] = $temp;
                            $col = $this->increment_keys ( $col, $params );
                            $params[$col] = $val;
                        }
                    } else {
                    }
                }
            }
            $where = ( !empty ( $wheres )) ? 'WHERE ' . implode ( ' AND ', $wheres ) : '';
        }

        // filter
        if ( is_array ( $filter )) {
            foreach ( $filter as $col => $sort ) {
                // validate column is real
                if ( is_array ( $table )) { // for an array of tables
                    foreach ( $table as $tbl => $join ) {
                        if ( true == $this->valid_column ( $tbl, $col )) {
                            $sort = ( 'ASC' == strtoupper ( $sort ) || 'DESC' == strtoupper ( $sort )) ? strtoupper ( $sort ) : 'ASC';
                            $temp = "`{$col}` {$sort}";
                            if ( !in_array ( $temp, $filters )) {
                                $filters[] = $temp;
                            }
                        }
                    }
                } else { // for a single table
                    if ( true == $this->valid_column ( $table, $col )) {
                        $sort = ( 'ASC' == strtoupper ( $sort ) || 'DESC' == strtoupper ( $sort )) ? strtoupper ( $sort ) : 'ASC';
                        $temp = "`{$col}` {$sort}";
                        if ( !in_array ( $temp, $filters )) {
                            $filters[] = $temp;
                        }
                    }
                }
            }
            $filter = ( !empty ( $filters )) ? 'ORDER BY ' . implode ( ', ', $filters ) : '';
        }

        // group
        if ( is_array ( $group )) {
            foreach ( $group as $col ) {
                // validate column is real
                if ( is_array ( $table )) { // for an array of tables
                    foreach ( $table as $tbl => $join ) {
                        if ( true == $this->valid_column ( $tbl, $col )) {
                            $temp = "`{$col}`";
                            if ( !in_array ( $temp, $groups )) {
                                $groups[] = $temp;
                            }
                        }
                    }
                } else { // for a single table
                    if ( true == $this->valid_column ( $table, $col )) {
                        $temp = "`{$col}`";
                        if ( !in_array ( $temp, $groups )) {
                            $groups[] = $temp;
                        }
                    }
                }
            }
            $group = ( !empty ( $groups )) ? "GROUP BY " . implode ( ', ', $groups ) : '';
        }

        // limit
        if ( is_numeric ( $limit ) || ( is_array ( $limit ) && 2 > count ( $limit ))) {
            $limit = ( is_array ( $limit )) ? "LIMIT " . end ( $limit ) . " " : "LIMIT {$limit} ";
        } elseif ( is_array ( $limit )) {
            list ( $start, $stop ) = $limit;
            if ( is_numeric ( $start ) && is_numeric ( $stop )) {
                $limit = "LIMIT {$start}, {$stop} ";
            } else {
                $limit = '';
            }
        }

        // tables and joins
        if ( is_array ( $table )) {
            // joins
            $joins = array (
                'LEFT JOIN',
                'LEFT OUTER JOIN',
                'INNER JOIN',
                'OUTER JOIN',
                'FULL JOIN',
                'FULL OUTER JOIN',
                'RIGHT JOIN',
                'RIGHT OUTER JOIN',
                'JOIN',
            );
            $join = ( in_array ( $join, $joins )) ? $join : 'LEFT OUTER JOIN';
            foreach ( $table as $table => $col ) {
                if ( true == $this->valid_table ( $table ) ) {
                    if ( is_array ( $col )) {
                        /* how to get previous and next tables to validate columns??????????
                        list ( $colA, $colB ) = $col;
                        if ( true == $this->valid_column ( $table, $colA ) && $this->valid_column ( $table, $colB )) {

                        }/***/
                    } else {
                        if ( true == $this->valid_column ( $table, $col )) {
                            $sql .= ( empty ( $sql )) ? "SELECT * FROM `{$table}` " : "{$join} `{$table}` USING ( `{$col}` ) ";
                        }
                    }
                }
            }
        } else {
            if ( true == $this->valid_table ( $table )) $sql = "SELECT * FROM {$table} ";
        }
        // logic and ordering
        $sql .= "{$where} {$filter} ";

        // grouping
        if ( !empty ( $group )) $sql = "SELECT * FROM ( {$sql} ) `table` {$group} ";

        // limit
        $sql .= "{$limit}";

        // count
        if ( true === $count ) $sql = "SELECT COUNT(*) FROM ( {$sql} ) `records` ";

        // execute and return
        $res = $this->sql_exec ( $sql, $params );

        // get count
        if ( true === $count ) $res = end ( $res[0] );

        // return query results
        return $res;
    }
    // Insert row into database and update on duplicate primary key
    public function insert_row($table, $params, $update = true) {
        // validate table
        if (false == $this->valid_table($table)) return false;

        // prep columns and binding placeholders
        $columns = array_keys($params);
        $cols = (count($params) > 1) ? implode("`,`", $columns) : current($columns);
        $binds = (count($params) > 1) ? implode(", :", $columns) : current($columns);

        // create base query
        $query = "INSERT INTO `{$table}` (`{$cols}`) VALUES (:{$binds})";

        // update on duplicate primary key
        if (true == $update) {						// if update is set to true
            $query .= " ON DUPLICATE KEY UPDATE ";	// append duplicate to query
            $schema = $this->column_types($table);	// get table column data
            foreach ($schema as $col) {				// loop through table columns
                if ('PRI' != $col['Key'] && array_key_exists($col['Field'], $params)) {
                    $updates[] = "`{$col['Field']}`=:update_{$col['Field']}";
                    $params["update_{$col['Field']}"] = $params[$col['Field']];
                }
            }
            $query .= implode(",", $updates);
        }

        // execute query
        $res = $this->sql_exec($query, $params);
        return $res;
    }
    // Update existing row from given key => value
    public function update_row($table, $params, $key) {
        // validate table
        if (false == $this->valid_table($table)) return false;
        if ($this->logDebug) $this->debug[] = "Valid table: {$table}";

        // parse updates
        foreach ($params as $col => $val) {
            // validate table column
            if ($this->valid_column($table, $col)) $updates[] = "`{$col}`=:{$col}";
            if ($this->logDebug) $this->debug[] = "Valid column: {$col}";
        }
        $updates = implode(',', $updates);

        // define where key
        foreach ($key as $col => $val) {
            if ($this->valid_column($table, $col)) $where = "`{$col}`='{$val}'";
            //$params[$col] = $val;
            if ($this->logDebug) $this->debug[] = "Valid update on: {$where}";
        }

        // compile and execute
        $query = "UPDATE `{$table}` SET {$updates} WHERE {$where}";
        return $this->sql_exec($query, $params);
    }
    // Delete a row
    public function delete_row( $table, $params ) {
        // check for valid table
        if (false == $this->valid_table($table)) return false;
        if ($this->logDebug) $this->debug[] = "Valid table: {$table}";

        foreach ($params as $col => $val) {
            if (false == $this->valid_column($table, $col)) return false;
            if ($this->logDebug) $this->debug[] = "Valid column: {$col}";

            $where = "`{$col}`=:{$col}";
        }

        // compile and execute
        $query = "DELETE FROM `{$table}` WHERE {$where};";
        return $this->sql_exec($query, $params);
    }
    // Increment column keys; a fetch_table helper function
    public function increment_keys ( $key, $arr ) {
        if ( is_array ( $arr ) && array_key_exists ( $key, $arr )) {
            $i = 2;
            while ( array_key_exists ( "{$key}{$i}", $arr )) $i++;
            return "{$key}{$i}";
        } else {
            return $key;
        }
    }

    ##	XX.0 Debug
    //	  ____       _
    //	 |  _ \  ___| |__  _   _  __ _
    //	 | | | |/ _ \ '_ \| | | |/ _` |
    //	 | |_| |  __/ |_) | |_| | (_| |
    //	 |____/ \___|_.__/ \__,_|\__, |
    //							 |___/

    // Get debug info of a prepared statement
    public function debug_stmt() {
        // get debug data
        echo "<pre>";
        $this->stmt->debugDumpParams();
        echo "</pre>";
    }
    // Show debug log
    public function show_debug_log() {
        // check if it's empty
        if (empty($this->debug)) {
            echo "<pre>no data in debug log.</pre>";
        } else {
            $this->disp($this->debug);
        }
    }
    // Clear debug log
    public function clear_debug_log() {
        unset ( $this->debug );
    }
    // Generate random string of a given length
    public function randstring($len, $includeSpaces = false) {
        // set vars
        $src = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        if (true === $includeSpaces) $src .= '   ';
        $string = '';

        do {
            // random character from source & append character to string
            $string .= substr($src, rand(0, strlen($src) - 1), 1);
        } while (strlen($string) < $len);

        return $string;
    }
    // show errors
    public function show_err() {
        // if error container array is not empty
        if (!empty($this->err)) $this->disp($this->err);
    }
    // display array
    public function disp($array, $backtrace = false) {
        //$debug = debug_backtrace();
        //echo "<pre>Display called from {$debug[1]['function']} line {$debug[1]['line']}\n\n";
        echo "<pre>";
        print_r($array);
        echo "</pre>";
    }
    // Get script resource usage
    public function resource_usage($reset = false) { # http://stackoverflow.com/questions/535020/tracking-the-script-execution-time-in-php
        if (true == $reset) unset($rustart);
        if (empty($rustart)) {
            static $rustart;
            $rustart = getrusage();
        } else {
            $rustop = getrusage();
            echo "This process used " . rutime($rustop, $rustart, "utime") . " ms for its computations\n";
            echo "It spent " . rutime($rustop, $rustart, "stime") . " ms in system calls\n";
        }
    }
}

?>