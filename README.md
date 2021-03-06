Google Visualization Data Source for PHP
========================================

This is a near literal translation of [google-visualization-java](https://code.google.com/p/google-visualization-java/source/browse/trunk/src/main/java/com/google/visualization/datasource/) into PHP.
However, the QueryParser class was written from scratch.
While its main purpose is to generate data formatted for Google Charts, it can also be used as an abstraction layer for accessing data from a variety of sources using a SQL-like query.
Thorough testing has not been performed, so bug reports are encouraged.  Enjoy!


Features
--------

- A PHP implementation of the [Google Chart Tools Datasource Protocol](https://developers.google.com/chart/interactive/docs/dev/implementing_data_source)
- Parses a [Google Visualization Query](https://developers.google.com/chart/interactive/docs/querylanguage) into a PHP object
- Executes the query on an existing DataTable or retrieves one using Helper classes:
    - PDO Helper abstract class with extensions that perform automatic type casting:
        - PostgreSQL
        - MS SQL Server / SQL Azure
        - MySQL
        - SQLite
- Outputs the result in the requested format (JSON, JSONP, HTML, or CSV)
- Complete support of the [Google Visualization Query Language](https://developers.google.com/chart/interactive/docs/querylanguage), with some additional functions (that use MySQL syntax):
    - ABS (absolute value of a number)
    - CONCAT (concatenate strings)
    - CONCAT_WS (concatenate strings with separator)
    - LEFT (left-most characters of a string)
    - RIGHT (right-most characters of a string)
    - ROUND (round a number to a digit of precision)


Dependencies
------------

- PHP 5.4+ (maybe 5.3)
    - intl extension (for error messages)
    - PDO extension (for PDO support)
        - Database driver extensions (for each driver you need to use)


Usage
-----

The usage is nearly similar to that of the [java library](https://developers.google.com/chart/interactive/docs/dev/dsl_about) (see that further usage help).
- Include all the files in the path or use an autoloader, such as [AutoloadByNamespace-php](https://github.com/bggardner/AutoloadByNamespace-php).
- For usage with Google Charts:
    - Create a class that extends the DataSource class
    - Instantiate the class in a file that accepts the HTTP GET request from the Google Chart
- Useful stand-alone functions if using as an abstraction layer:
    - DataSourceHelper::parseQuery($string) - Returns a Query object from $string
    - MySqlPdoDataSourceHelper::executeQuery(Query $query, PDO $pdo, $tableNmae) - Returns a DataTable object by applying the query to a MySQL table 
    - DataSourceHelper::applyQuery(Query $query, DataTable $dataTable, $locale) - Returns a DataTable object by applying the query to a DataTable


Examples
--------

Query a table named "mytable" from a SQL database, using AutoloadByNamespace:
```php
<?php
  // Required to autoload the Google\Visualization\DataSource classes
  require_once "/path/to/AutoloadByNamespace.php";
  spl_autoload_register("AutoloadByNamespace::autoload");
  AutoloadByNamespace::register("Google\Visualization\DataSource", "/path/to/google-visualization-php");

  // The custom class that defines how the data is generated
  class MyDataSource extends Google\Visualization\DataSource\DataSource
  {
    public function getCapabilities() { return Google\Visualization\DataSource\Capabilities::SQL; }

    public function generateDataTable(Google\Visualization\DataSource\Query\Query $query)
    {
      // MySQL
      $pdo = new PDO("mysql:host=xxx;port=xxx;dbname=xxx", "username", "password");
      return Google\Visualization\DataSource\Util\Pdo\MysqlPdoDataSourceHelper::executeQuery($query, $pdo, "mytable");

      // MS SQL Server / SQL Azure
      $pdo = new PDO("sqlsrv:Server=xxx;Database=xxx", "username", "password");
      return Google\Visualization\DataSource\Util\Pdo\MssqlserverPdoDataSourceHelper::executeQuery($query, $pdo, "mytable");

      // PostgreSQL
      $pdo = new PDO("pgsql:host=xxx;port=xxx;dbname=xxx", "username", "password");
      return Google\Visualization\DataSource\Util\Pdo\PostgresqlPdoDataSourceHelper::executeQuery($query, $pdo, "mytable");

      // SQLite
      $pdo = new PDO("sqlite:/path/to/xxx.db");
      return Google\Visualization\DataSource\Util\Pdo\SqlitePdoDataSourceHelper::executeQuery($query, $pdo, "mytable");
    }

    public function isRestrictedAccessMode() { return FALSE; }
  }

  // Instantiating the class parses the 'tq' and 'tqx' HTTP request parameters and outputs the resulting data
  new MyDataSource();
?>
```
Query a CSV file (with known column order and data types), using spl_autoload_register:
```php
<?php
  spl_autoload_register(function($class) {
    $class = str_replace('Google\\Visualization\\DataSource\\', '', $class);
    require_once '/path/to/google-visualization-php/' . str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';
  });

  class MyDataSource extends Google\Visualization\DataSource\DataSource
  {
    public function getCapabilities() { return Google\Visualization\DataSource\Capabilities::NONE; }

    public function generateDataTable(Google\Visualization\DataSource\Query\Query $query = NULL)
    {
      // Since Capabilities are NONE, the $query argument will be NULL as the data will be processed by DataSourceHelper

      // Create the DataTable and configure the columns (name and data type)
      $dataTable = new Google\Visualization\DataSource\DataTable\DataTable();
      $columnDescriptions = array();
      $columnDescriptions[] = new Google\Visualization\DataSource\DataTable\ColumnDescription("x", Google\Visualization\DataSource\DataTable\Value\ValueType::NUMBER, "x");
      $columnDescriptions[] = new Google\Visualization\DataSource\DataTable\ColumnDescription("y", Google\Visualization\DataSource\DataTable\Value\ValueType::NUMBER, "y");
      $dataTable->addColumns($columnDescriptions);

      // Populate the DataTable
      $i = 0;
      $fh = fopen('data.csv', 'r');
      while (($data = fgetcsv($fh)) !== FALSE)
      {
        $tableRow = new Google\Visualization\DataSource\DataTable\TableRow();
        foreach ($data as $datum)
        {
          $value = new Google\Visualization\DataSource\DataTable\Value\NumberValue($datum);
          $tableCell = new Google\Visualization\DataSource\DataTable\TableCell($value);
          $tableRow->addCell($tableCell);
        }
        $dataTable->addRow($tableRow);
      }
      return $dataTable;
    }

    public function isRestrictedAccessMode() { return FALSE; }
  }

  new MyDataSource();
?>
```
