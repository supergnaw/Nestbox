# Nestbox [WIP]

A interface for databases using PHP Data Objects written to easily fill gaps of niche requirements. This project is updated as needs arise and is probably a result of NIH syndrome.

## Nestbox Birds
Each bird *(class)* in the "nestbox" serves as a way to add specific functionality to Nestbox.

| Bird                             | Description |
|----------------------------------| --- |
| [Babbler](Babbler/readme.md)     | A flexible content management system designed with basic website/blog editing functioality in mind. |
| [Bullfinch](Bullfinch/readme.md) | An interface designed to easily create and deploy a simple message board. *(Not yet complete/available)* |
| [Cuckoo](Cuckoo/readme.md)       | MySQL database in-line encryption for data at rest. *(Not yet complete/available)* |
| [Lorikeet](Lorikeet/readme.md)   | An image upload processing and indexing. *(Not yet complete/available)* |
| [Magpie](Magpie/readme.md)       | A user and group permissions manager. |
| [Myna](Myna/readme.md)           | An API endpoint management system for easy REST API building. *(Not yet complete/available)* |
| [Titmouse](Titmouse/readme.md)   | A user management interface that can register/login/logout users while adhering to standard practicces for password handling. |

### Basic Usage
The Nestbox class was designed for simplistic usage for database interaction while incorporating best practices for safely interacting with the database.

```php
use Supergnaw\Nestbox;

$nest = new Nestbox();

try {
    if( $nest->query_execute( "SELECT * FROM `users`;" )) {
        $users = $nest->results();
    }
} catch ( NestboxException $exception ) {
    die( $exception->getMessage());
}
```

### Database Details
The database connection is defined through four constants:
- `NESTBOX_DB_HOST`: the database host ( default: `'localhost'` )
- `NESTBOX_DB_USER`: the database username ( default: `'root'` )
- `NESTBOX_DB_PASS`: the database username ( default: `''` )
- `NESTBOX_DB_NAME`: the database username ( default: `''` )

These constants can be defined in a singularly called file, however if they are not, the class defines them automatically upon instantiation.

## Quick Queries
Nestbox has three built-in functions designed to interact with a database some of the most common forms of database manipulation: `insert()`, `update()`, and `delete()`. Their purpose is to simplify the process by internally building prepared statements using the provided data. All values are passed as parameters using `:named` placeholders. Additionally, table and column names are verified against the database schema with any inconsistencies throwing an exception.

### Insert
```php
$nest->insert( string $table, array $params, bool $update = true ): int
```
- `$table`: a string designating the table name
- `$params`: an array of ['column' => 'value'] parameters to insert into `$table`
- `$update`: a boolean indicating update on duplicate key; default is true

The return value is `int` type of the number of rows inserted.

### Update
```php
$nest->update( string $table, array $params, array $where, string $conjunction = "AND" ): int
```
- `$table`: a string designating the table name
- `$params`: an array of ['column' => 'value'] parameters to update in `$table`
- `$where`: an array of ['column' => 'value'] parameters to determine "where" the update will take place, e.g. `['user_id' => 123]`
- `$conjunction`: a string indicating how each $where parameter will be joined. The only two supported options are: `AND`, `OR`

The return value is `int` type of the number of rows affected.

*Please note that `0` doesn't necessarily mean the query failed to execute, just that no values were changed.*

### Delete
```php
$nest->delete( string $table, array $where, string $conjunction = "AND" ): int
```
- `$table`: a string designating the table name
- `$where`: an array of ['column' => 'value'] parameters to determine "where" the deletion will take place, e.g. `['user_id' => 123]`
- `$conjunction`: a string indicating how each $where parameter will be joined. The only two supported options are: `AND`, `OR`

The return value is `int` type of the number of rows affected.

## Transactions
Transactions help with data integrity across multiple tables within the database.
```php
$nest->transaction( $query, $params, $commit ): bool
```


The following is an example of how a transaction could be implemented.
```php
$accountChanges = ['account_id' => 'a3f8e0d49', 'amount' => 300];
$nest->transaction( "INSERT INTO `account_changes` ( `account_id`, `amount` ) VALUES ( :account, :amount );", $accountChanges );
$newAccountBalance = ['account_id' => 'a3f8e0d49', 'balance' => $oldBalance - 300];
$nest->transaction( "UPDATE `accounts` SET `balance` = :balance WHERE `account_id` = :account_id;", $newBalance, true );
```

### Committing Transactions

## Database Schema
There are three functions that can be used for determining the validity of the database schema. These are used in the quick queries to build prepared statements with variable table or column names, and can also be used to build expanded functionality to query building.

## Exceptions
*todo: add more documentation*

# References
Since this was a project designed for learning, here are some great references used during the creation of this project:
- [(The only proper) PDO tutorial](https://phpdelusions.net/pdo)
