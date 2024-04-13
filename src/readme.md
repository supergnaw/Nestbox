# Nestbox

An interface for databases using PHP Data Objects written to easily fill gaps of niche requirements. This project is
updated as needs arise and is probably a result of NIH syndrome.

## Basic Usage

The Nestbox class was designed for simplistic usage for database interaction while incorporating best practices for
safely interacting with the database.

```php
use Supergnaw\Nestbox;

$nb = new Nestbox();
$sql = "SELECT * FROM `users`;";

try {
    if ($nb->query_execute($sql)) {
        $users = $nb->results();
    }
} catch (NestboxException $exception ) {
    die ($exception->getMessage());
}
```

### Database Connection Details

Nestbox database connection defaults can be defined using four constants:

```php
// optionally set global constants
define('NESTBOX_DB_HOST', "localhost");
define('NESTBOX_DB_USER', "root");
define('NESTBOX_DB_PASS', "correct horse battery staple");
define('NESTBOX_DB_NAME', "nestbox_database");
```

If these constants are not defined, Nestbox will attempt to use the parameters passed when a new instance is created or
invoked. The benefit of being able to pass unique connection parameters (such as a new database name
or [password](https://xkcd.com/936/)) to a given instance is in the edge case where a given project requires a different
connection to a separate database.

```php
// create a new instance using defined constants
$nb = new \Supergnaw\Nestbox\Nestbox();

// use the existing instance to create a new connection
$nb(host: $host, user: $user, pass: $pass, name: $name);
```

## Basic Queries

Nestbox has three built-in functions designed to interact with a database using some of the most common forms of
database manipulation: `insert()`, `update()`, `delete()`, and `select()`. Their purpose is to simplify the process by
internally
building prepared statements using the provided data. All values are passed as parameters using `:named` placeholders.
Additionally, table and column names are verified against the database schema with any inconsistencies throwing an
exception.

### Insert

```php
insert(string $table, array $params, bool $update = true): int
```

- `$table`: a string designating the table name
- `$params`: an array of ['column' => 'value'] parameters to insert into `$table`
- `$update`: a boolean indicating update on duplicate key; default is true

The return value is `int` type of the number of rows inserted.

### Update

```php
update(string $table, array $params, array $where, string $conjunction = "AND"): int
```

- `$table`: a string designating the table name
- `$params`: an array of ['column' => 'value'] parameters to update in `$table`
- `$where`: an array of ['column' => 'value'] parameters to determine "where" the update will take place,
  e.g. `['user_id' => 123]`
- `$conjunction`: a string indicating how each $where parameter will be joined. The only two supported options
  are: `AND`, `OR`

The return value is `int` type of the number of rows affected.

*Please note that a return value of `0` does not necessarily mean a query failed to execute, rather no values were
changed.*

### Delete

```php
delete(string $table, array $where, string $conjunction = "AND"): int
```

- `$table`: a string designating the table name
- `$where`: an array of ['column' => 'value'] parameters to determine "where" the deletion will take place,
  e.g. `['user_id' => 123]`
- `$conjunction`: a string indicating how each `$where` parameter will be joined. The only two supported options
  are: `AND` and `OR`, case insensitive

The return value is of type `int` containing the number of affected rows.

### Select

```php
select(string $table, array $where = [], string $conjunction = "AND"): array
```

- `$table`: a string desinating the table name
- `$where`: an array of ['column' => 'value'] parameters to determine which rows of matching values to select,
  e.g. `['user_id' => 123]`
- `$conjunction`: a string indicating how each `$where` parameter will be joined. The only two valid options
  are: `AND` and `OR`, case insensitive

## Transactions

Transactions help with data integrity during simultaneous updates across multiple tables within the database.

### Transaction _(currently broken)_

The `transaction()` function can be used to step through a transaction execution one query at a time.

```php
transaction(string $query, array $params, bool $commit = false, bool $close = false): bool
```

The following is an example of how a transaction could be implemented and committed.

```php
// prepare first queries
$query1 = "INSERT INTO `my_table_1` (`col_1`, `col_2`) VALUES (:val1, :val2);";
$params1 = ["val1" => "foo", "val2" => "bar"];

// execute next step (note commit is set to false)
$results1 = $nb->transaction(query: $query1, params: $params1, commit: false);

// prepare final queries
$query2 = "INSERT INTO `my_table2' (`col_2`, `col_4`) VALUES (:val1, :val2);";
$params2 = ["val1" => "foo", "val2" => "bar"];

// execute and commit transaction (note commit is set to true)
$results2 = $nb->transaction(query: $query2, params: $params2, commit: true);
```

### Rollback

```php
rollback(): bool
```

Attempts to rollback the current transaction. Returns `true` on success or `false` on failure or if no transaction is
currently in progress.

### Transaction Execute _(untested)_

The `transaction_execute()` function takes an array of queries and parameters, attempts to execute and commit all
queries provided, and finally attempts to do a database rollback when errors are encountered. The returned array will
have the results for each individual query executed in the same order that they were executed.

```php
transaction_execute(array $queries): array
```

The format of the `$queries` array is a multidimensional array where each element of the root aray is an array having a
string key of the query and a value being an array of parameters.

```php
$queries = [
    "SELECT * FROM `table` WHERE `column_name` = :column_name;" => ['column_name' => "column_value"],
    // more queries go here
    // ...
];

$results = $nb->transaction_execute($queries);
```

## Database Schema

Nestbox has internal functions to validate the database schema of quick queries which can also be used separately when
dynamically building application-specific queries.

```php
valid_schema(string $table, string $column = null): bool
````

* returns `true` if `$table` is a valid table name, otherwise false
* returns `true` if `$table` is a valid table name and contains `$column`, otherwise `false`

```php
valid_table(string $table): bool
````

* returns `true` if `$table` is a valid table name, otherwise `false`

```php
valid_column(string $table, string $column): bool
````

* returns `true` if `$table` is a valid table name and contains `$column`, otherwise `false`

```php
valid_trigger(string $table, string $trigger): bool
````

* returns `true` if `$table` is a valid table name and has a trigger named `$trigger`, otherwise `false`

## Exceptions

***todo: add more documentation here***

## Further Reading

- [(The only proper) PDO tutorial](https://phpdelusions.net/pdo)
