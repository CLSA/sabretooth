<?php
/**
 * database.class.php
 * For now see {@link connect} for the current hack/solution.
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\database
 * @filesource
 */

namespace sabretooth\database;
use sabretooth\log, sabretooth\util;
use sabretooth\business as bus;
use sabretooth\exception as exc;

/**
 * @category external
 */
require_once ADODB_PATH.'/adodb.inc.php';

/**
 * The database class represents a database connection and information.
 * @package sabretooth\database
 */
class database extends \sabretooth\base_object
{
  /**
   * Constructor
   * 
   * The constructor either creates a new connection to a database.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $driver The type of database (only mysql is tested)
   * @param string $server The name of the database's server
   * @param string $username The username to connect with.
   * @param string $password The password to connect with.
   * @param string $database The name of the database.
   * @param string $prefix The prefix to add before every table name.
   * @throws exception\runtime
   * @access public
   */
  public function __construct( $driver, $server, $username, $password, $database, $prefix )
  {
    $this->driver = 'mysql' == $driver ? 'mysqlt' : $driver;
    $this->server = $server;
    $this->username = $username;
    $this->password = $password;
    $this->name = $database;
    $this->prefix = $prefix;
    
    // set up the database connection
    $this->connection = ADONewConnection( $this->driver );
    $this->connection->SetFetchMode( ADODB_FETCH_ASSOC );
    
    $this->connect();

    $column_mod = new modifier();
    $column_mod->where( 'TABLE_SCHEMA', '=', $this->name );
    $column_mod->order( 'TABLE_NAME' );
    $column_mod->order( 'COLUMN_NAME' );

    $rows = $this->get_all(
      sprintf( 'SELECT TABLE_NAME AS table_name, '.
                      'COLUMN_NAME AS column_name, '.
                      'COLUMN_TYPE AS column_type, '.
                      'DATA_TYPE AS data_type, '.
                      'COLUMN_KEY AS column_key, '.
                      'COLUMN_DEFAULT AS column_default '.
               'FROM information_schema.COLUMNS %s',
               $column_mod->get_sql() ) );
    
    // record the tables, columns and types
    foreach( $rows as $row )
    {
      extract( $row ); // defines $table_name, $column_name and $column_type
      if( 'update_timestamp' != $column_name && // ignore timestamp columns
          'create_timestamp' != $column_name )
      {
        if( !array_key_exists( $table_name, $this->columns ) )
          $this->columns[$table_name] = array();

        $this->columns[$table_name][$column_name] =
          array( 'data_type' => $data_type,
                 'type' => $column_type,
                 'default' => $column_default,
                 'key' => $column_key );
      }
    }

    $constraint_mod = new modifier();
    $constraint_mod->where( 'TABLE_CONSTRAINTS.TABLE_SCHEMA', '=', $this->name );
    $constraint_mod->where( 'KEY_COLUMN_USAGE.TABLE_SCHEMA', '=', $this->name );
    $constraint_mod->where( 'TABLE_CONSTRAINTS.CONSTRAINT_TYPE', '=', 'UNIQUE' );
    $constraint_mod->where(
      'TABLE_CONSTRAINTS.CONSTRAINT_NAME', '=', 'KEY_COLUMN_USAGE.CONSTRAINT_NAME', false );
    $constraint_mod->group( 'table_name' );
    $constraint_mod->group( 'constraint_name' );
    $constraint_mod->group( 'column_name' );
    $constraint_mod->order( 'table_name' );
    $constraint_mod->order( 'constraint_name' );
    $constraint_mod->order( 'column_name' );
    
    $rows = $this->get_all(
      sprintf( 'SELECT TABLE_CONSTRAINTS.TABLE_NAME table_name, '.
                      'TABLE_CONSTRAINTS.CONSTRAINT_NAME AS constraint_name, '.
                      'KEY_COLUMN_USAGE.COLUMN_NAME AS column_name '.
               'FROM information_schema.TABLE_CONSTRAINTS, information_schema.KEY_COLUMN_USAGE %s',
               $constraint_mod->get_sql() ) );
    
    // record the tables, columns and types
    foreach( $rows as $row )
    {
      extract( $row ); // defines $table_name, $constraint_name and $column_name
      if( !array_key_exists( $table_name, $this->tables ) )
        $this->tables[$table_name] = array();
      if( !array_key_exists( 'constraints', $this->tables[$table_name] ) )
        $this->tables[$table_name]['constraints'] = array();

      $this->tables[$table_name]['constraints'][$constraint_name][] = $column_name;
    }
  }

  /**
   * Destructor
   * 
   * The main application database completes its transaction at destruction.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function __destruct()
  {
    // only complete a transaction for the main database (this is an ADOdb limitation)
    if( class_exists( '\sabretooth\business\setting_manager' ) &&
        bus\setting_manager::exists() &&
        bus\setting_manager::self()->get_setting( 'db', 'database' ) == $this->name )
      $this->connection->CompleteTrans();
  }
  
  public function start_transaction()
  {
    // only start a transaction for the main database (this is an ADOdb limitation)
    if( bus\setting_manager::self()->get_setting( 'db', 'database' ) == $database )
      $this->connection->StartTrans();
  }

  /**
   * Fail the current transaction
   * 
   * Calling this method causes the current transaction to fail, causing any changes to the
   * database to be rolled back when the transaction completes.
   * The transaction will automatically fail if there is a database error, this method should
   * only be used when a transaction should fail because of a non-database error.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function fail_transaction()
  {
    $this->connection->FailTrans();
  }

  /**
   * Get's the name of the database.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return string
   * @access public
   */
  public function get_name() { return $this->name; }

  /**
   * Get's the prefix of the database.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return string
   * @access public
   */
  public function get_prefix() { return $this->prefix; }

  /**
   * Determines whether a particular table exists.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $table_name The name of the table to check for.
   * @return boolean
   * @access public
   */
  public function table_exists( $table_name )
  {
    $table_name = $this->prefix.$table_name;
    return array_key_exists( $table_name, $this->columns );
  }

  /**
   * Returns whether the record's associated table has a specific column name.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $table_name A table name.
   * @param string $column_name A column name
   * @return boolean
   * @access public
   */
  public function column_exists( $table_name, $column_name )
  {
    $table_name = $this->prefix.$table_name;
    return array_key_exists( $table_name, $this->columns ) &&
           array_key_exists( $column_name, $this->columns[$table_name] );
  }
  
  /**
   * Returns an array of column names for the given table.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $table_name A table name.
   * @return array( string )
   * @access public
   */
  public function get_column_names( $table_name )
  {
    if( !$this->table_exists( $table_name ) )
      throw new exc\runtime(
        sprintf( "Tried to get column names for table '%s' which doesn't exist.",
                 $table_name ), __METHOD__ );

    $table_name = $this->prefix.$table_name;
    return array_keys( $this->columns[$table_name] );
  }

  /**
   * Returns a column's type (int, varchar, enum, etc)
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $table_name The name of the table to check for.
   * @param string $column_name A column name in the record's corresponding table.
   * @return string
   * @access public
   */
  public function get_column_type( $table_name, $column_name )
  {
    if( !$this->column_exists( $table_name, $column_name ) )
      throw new exc\runtime(
        sprintf( "Tried to get column type for '%s.%s' which doesn't exist.",
                 $table_name,
                 $column_name ), __METHOD__ );

    $table_name = $this->prefix.$table_name;
    return $this->columns[$table_name][$column_name]['type'];
  }
  
  /**
   * Returns a column's data type (int(10) unsigned, varchar(45), enum( 'a', 'b', 'c' ), etc)
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $table_name The name of the table to check for.
   * @param string $column_name A column name in the record's corresponding table.
   * @return string
   * @access public
   */
  public function get_column_data_type( $table_name, $column_name )
  {
    if( !$this->column_exists( $table_name, $column_name ) )
      throw new exc\runtime(
        sprintf( "Tried to get column data type for '%s.%s' which doesn't exist.",
                 $table_name,
                 $column_name ), __METHOD__ );

    $table_name = $this->prefix.$table_name;
    return $this->columns[$table_name][$column_name]['data_type'];
  }
  
  /**
   * Returns a column's key type.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $table_name The name of the table to check for.
   * @param string $column_name A column name in the record's corresponding table.
   * @return string
   * @access public
   */
  public function get_column_key( $table_name, $column_name )
  {
    if( !$this->column_exists( $table_name, $column_name ) )
      throw new exc\runtime(
        sprintf( "Tried to get column key for '%s.%s' which doesn't exist.",
                 $table_name,
                 $column_name ), __METHOD__ );

    $table_name = $this->prefix.$table_name;
    return $this->columns[$table_name][$column_name]['key'];
  }
  
  /**
   * Returns a column's default.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $table_name The name of the table to check for.
   * @param string $column_name A column name in the record's corresponding table.
   * @return string
   * @access public
   */
  public function get_column_default( $table_name, $column_name )
  {
    if( !$this->column_exists( $table_name, $column_name ) )
      throw new exc\runtime(
        sprintf( "Tried to get column default for '%s.%s' which doesn't exist.",
                 $table_name,
                 $column_name ), __METHOD__ );

    $table_name = $this->prefix.$table_name;
    return $this->columns[$table_name][$column_name]['default'];
  }
  
  /**
   * This method returns an array of unique keys with the key-value pair being the key's name
   * and an array of column names belonging to that key, respectively.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $table_name The name of the table to check for.
   * @return associative array.
   * @access public
   */
  public function get_unique_keys( $table_name )
  {
    if( !$this->table_exists( $table_name ) )
      throw new exc\runtime(
        sprintf( "Tried to get unique keys for table '%s' which doesn't exist.", $table_name ),
        __METHOD__ );

    $table_name = $this->prefix.$table_name;
    return $this->tables[$table_name]['constraints'];
  }
  
  /**
   * Gets the primary key names for a given table.
   * Note: This is a wrapper for ADOdb::MetaPrimaryKeys()
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return array( string )
   * @param string $table_name A table name.
   * @access public
   */
  public function meta_primary_keys( $table_name )
  {
    $table_name = $this->prefix.$table_name;
    $this->connect();
    return $this->connection->MetaPrimaryKeys( $table_name );
  }

  /**
   * Database convenience method.
   * 
   * Execute SQL statement $sql and return derived class of ADORecordSet if successful. Note that a
   * record set is always returned on success, even if we are executing an insert or update
   * statement.
   * Note: This is a wrapper for ADOdb::Execute()
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $sql SQL statement
   * @return ADORecordSet
   * @throws exception\database
   * @access public
   */
  public function execute( $sql )
  {
    $this->connect();
    $result = $this->connection->Execute( $sql );
    if( false === $result )
    {
      // pass the db error code instead of a class error code
      throw new exc\database(
        $this->connection->ErrorMsg(), $sql, $this->connection->ErrorNo() );
    }

    return $result;
  }
  
  /**
   * Database convenience method.
   * 
   * Executes the SQL and returns the first field of the first row.
   * Note: This is a wrapper for ADOdb::GetOne()
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $sql SQL statement
   * @return native or NULL if no records were found.
   * @throws exception\database
   * @access public
   */
  public function get_one( $sql )
  {
    $this->connect();
    $result = $this->connection->GetOne( $sql );
    if( false === $result )
    {
      // pass the db error code instead of a class error code
      throw new exc\database(
        $this->connection->ErrorMsg(), $sql, $this->connection->ErrorNo() );
    }

    return $result;
  }
  
  /**
   * Database convenience method.
   * 
   * Executes the SQL and returns the first row as an array.
   * Note: This is a wrapper for ADOdb::GetRow()
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $sql SQL statement
   * @return array (empty if no records are found)
   * @throws exception\database
   * @access public
   */
  public function get_row( $sql )
  {
    $this->connect();
    $result = $this->connection->GetRow( $sql );
    if( false === $result )
    {
      // pass the db error code instead of a class error code
      throw new exc\database(
        $this->connection->ErrorMsg(), $sql, $this->connection->ErrorNo() );
    }

    return $result;
  }
  
  /**
   * Database convenience method.
   * 
   * Executes the SQL and returns the all the rows as a 2-dimensional array.
   * Note: This is a wrapper for ADOdb::GetAll()
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $sql SQL statement
   * @return array (empty if no records are found)
   * @throws exception\database
   * @access public
   */
  public function get_all( $sql )
  {
    $this->connect();
    $result = $this->connection->GetAll( $sql );
    if( false === $result )
    {
      // pass the db error code instead of a class error code
      throw new exc\database(
        $this->connection->ErrorMsg(), $sql, $this->connection->ErrorNo() );
    }

    return $result;
  }
  
  /**
   * Database convenience method.
   * 
   * Executes the SQL and returns all elements of the first column as a 1-dimensional array.
   * Note: This is a wrapper for ADOdb::GetCol()
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $sql SQL statement
   * @param boolean $trim determines whether to right trim CHAR fields
   * @return array (empty if no records are found)
   * @throws exception\database
   * @access public
   */
  public function get_col( $sql, $trim = false )
  {
    $this->connect();
    $result = $this->connection->GetCol( $sql, $trim );
    if( false === $result )
    {
      // pass the database error code instead of a class error code
      throw new exc\database(
        $this->connection->ErrorMsg(), $sql, $this->connection->ErrorNo() );
    }

    return $result;
  }
  
  /**
   * Database convenience method.
   * 
   * Returns the last autonumbering ID inserted.
   * Note: This is a wrapper for ADOdb::Insert_ID()
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return int
   * @access public
   */
  public function insert_id()
  {
    $this->connect();
    return $this->connection->Insert_ID();
  }
  
  /**
   * Database convenience method.
   * 
   * Returns the number of rows affected by a update or delete statement.
   * Note: This is a wrapper for ADOdb::Affected_Rows()
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return int
   * @access public
   */
  public function affected_rows()
  {
    $this->connect();
    return $this->connection->Affected_Rows();
  }
  
  /**
   * Returns the string formatted for database queries.
   * 
   * The returned value will be put in double quotes unless the input is null in which case NULL
   * is returned.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $string The string to format for use in a query.
   * @return string
   * @static
   * @access public
   */
  public static function format_string( $string )
  {
    // NULL values are returned as a MySQL NULL value
    if( is_null( $string ) ) return 'NULL';
    
    // boolean values must be converted to strings (without double-quotes)
    if( is_bool( $string ) ) return $string ? 'true' : 'false';

    // trim whitespace from the begining and end of the string
    if( is_string( $string ) ) $string = trim( $string );
    
    return 0 == strlen( $string ) ? 'NULL' : '"'.mysql_real_escape_string( $string ).'"';
  }
  
  /**
   * Returns whether the column name is of type "date"
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $column_name Any (generic) column name
   * @return boolean
   * @static
   * @access public
   */
  public static function is_date_column( $column_name )
  {
    return 'date' == $column_name || '_date' == substr( $column_name, -5 );
  }

  /**
   * Returns whether the column name is of type "time"
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $column_name Any (generic) column name
   * @return boolean
   * @static
   * @access public
   */
  public static function is_time_column( $column_name )
  {
    return 'time' == $column_name || '_time' == substr( $column_name, -5 );
  }

  /**
   * Returns whether the column name is of type "datetime"
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $column_name Any (generic) column name
   * @return boolean
   * @static
   * @access public
   */
  public static function is_datetime_column( $column_name )
  {
    return 'datetime' == $column_name ||
           '_datetime' == substr( $column_name, -9 );
  }

  /**
   * Since ADODB does not support multiple database with the same driver this method must be
   * called before using the connection member.
   * This method is necessary because ADODB cannot connect to more than one database of the
   * same driver at the same time:
   * http://php.bigresource.com/ADODB-Multiple-Database-Connection-wno2zASC.html
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access private
   */
  private function connect()
  {
    if( $this->name != static::$current_database )
    {
      if( false == $this->connection->Connect(
        $this->server, $this->username, $this->password, $this->name ) )
        throw new exc\runtime(
          sprintf( 'Unable to connect to the "%s" database.', $this->name ), __METHOD__ );
      static::$current_database = $this->name;
    }
  }

  /**
   * Holds all table column types in an associate array where table => ( column => type )
   * @var array
   * @access private
   */
  private $columns = array();

  /**
   * Holds all table information including unique key constraints.
   * @var array
   * @access private
   */
  private $tables = array();

  /**
   * A reference to the ADODB resource.
   * @var resource
   * @access private
   */
  private $connection;

  /**
   * Tracks which database was connected to last.
   * @var string
   * @static
   * @access private
   */
  private static $current_database = '';

  /**
   * The database driver (see ADODB for possible values)
   * @var string
   * @access private
   */
  private $driver;

  /**
   * The server that the database is located
   * @var string
   * @access private
   */
  private $server;
  
  /**
   * Which username to use when connecting to the database
   * @var string
   * @access private
   */
  private $username;
  
  /**
   * Which password to use when connecting to the database
   * @var string
   * @access private
   */
  private $password;

  /**
   * The name of the database.
   * @var string
   * @access private
   */
  private $name;

  /**
   * The table name prefix.
   * @var string
   * @access private
   */
  private $prefix;
}
?>
