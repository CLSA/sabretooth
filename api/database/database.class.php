<?php
/**
 * database.class.php
 * TODO: may need to dump ADODB because of no-multiple connections bug:
 * http://php.bigresource.com/ADODB-Multiple-Database-Connection-wno2zASC.html
 * TODO: limesurvey db connection still not working right
 * For now see {@link connect} for the current hack/solution.
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\database
 * @filesource
 */

namespace sabretooth\database;

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
    $this->driver = $driver;
    $this->server = $server;
    $this->username = $username;
    $this->password = $password;
    $this->database = $database;
    $this->prefix = $prefix;
    
    // set up the database connection
    $this->connection = ADONewConnection( $driver );
    $this->connection->SetFetchMode( ADODB_FETCH_ASSOC );
    
    if( false == $this->connection->Connect( $server, $username, $password, $database ) )
      throw new \sabretooth\exception\runtime(
        "Unable to connect to the '$database' database.", __METHOD__ );

    $modifier = new modifier();
    $modifier->where( 'TABLE_SCHEMA', '=', $this->database );
    $modifier->order( 'TABLE_NAME' );
    $modifier->order( 'COLUMN_NAME' );

    $rows = $this->get_all(
      sprintf( 'SELECT TABLE_NAME AS table_name, COLUMN_NAME AS column_name, DATA_TYPE AS column_type '.
               'FROM information_schema.COLUMNS %s',
               $modifier->get_sql() ) );
    
    // record the tables, columns and types
    foreach( $rows as $row )
    {
      extract( $row ); // defines $table_name, $column_name and $column_type
      if( !array_key_exists( $table_name, $this->columns ) ) $this->columns[$table_name] = array();
      $this->columns[$table_name][$column_name] = $column_type;
    }
  }

  /**
   * Get's the name of the database.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return string
   * @access public
   */
  public function get_name() { return $this->database; }

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
      throw new \sabretooth\exception\runtime(
        sprintf( "Tried to get column names for table '%s' which doesn't exist.",
                 $table_name ), __METHOD__ );

    $table_name = $this->prefix.$table_name;
    return array_keys( $this->columns[$table_name] );
  }

  /**
   * Returns a column's data type.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $table_name The name of the table to check for.
   * @param string $column_name A column name in the record's corresponding table.
   * @return string
   * @access public
   */
  public function get_column_type( $table_name, $column_name )
  {
    if( !$this->column_exists( $table_name, $column_name ) )
      throw new \sabretooth\exception\runtime(
        sprintf( "Tried to get column type for '%s.%s' which doesn't exist.",
                 $table_name,
                 $column_name ), __METHOD__ );

    $table_name = $this->prefix.$table_name;
    return $this->columns[$table_name][$column_name];
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
   * @param array $input_array binding variables to parameters
   * @return ADORecordSet
   * @throws exception\database
   * @access public
   */
  public function execute( $sql, $input_array = false )
  {
    $this->connect();
    $result = $this->connection->Execute( $sql, $input_array );
    if( false === $result )
    {
      // pass the db error code instead of a class error code
      throw new \sabretooth\exception\database(
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
   * @param array $input_array binding variables to parameters
   * @return native or NULL if no records were found.
   * @throws exception\database
   * @access public
   */
  public function get_one( $sql, $input_array = false )
  {
    $this->connect();
    $result = $this->connection->GetOne( $sql, $input_array );
    if( false === $result )
    {
      // pass the db error code instead of a class error code
      throw new \sabretooth\exception\database(
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
   * @param array $input_array binding variables to parameters
   * @return array (empty if no records are found)
   * @throws exception\database
   * @access public
   */
  public function get_row( $sql, $input_array = false )
  {
    $this->connect();
    $result = $this->connection->GetRow( $sql, $input_array );
    if( false === $result )
    {
      // pass the db error code instead of a class error code
      throw new \sabretooth\exception\database(
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
   * @param array $input_array binding variables to parameters
   * @return array (empty if no records are found)
   * @throws exception\database
   * @access public
   */
  public function get_all( $sql, $input_array = false )
  {
    $this->connect();
    $result = $this->connection->GetAll( $sql, $input_array );
    if( false === $result )
    {
      // pass the db error code instead of a class error code
      throw new \sabretooth\exception\database(
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
   * @param array $input_array binding variables to parameters
   * @param boolean $trim determines whether to right trim CHAR fields
   * @return array (empty if no records are found)
   * @throws exception\database
   * @access public
   */
  public function get_col( $sql, $input_array = false, $trim = false )
  {
    $this->connect();
    $result = $this->connection->GetCol( $sql, $input_array, $trim );
    if( false === $result )
    {
      // pass the database error code instead of a class error code
      throw new \sabretooth\exception\database(
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
    // NULL values are returns as a MySQL NULL value
    if( is_null( $string ) ) return 'NULL';
    
    // trim whitespace from the begining and end of the string
    if( is_string( $string ) ) $string = trim( $string );
    
    return 0 == strlen( $string ) ? 'NULL' : '"'.mysql_real_escape_string( $string ).'"';
  }
  
  /**
   * Since ADODB does not support multiple database with the same driver this method must be
   * called before using the connection member.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access private
   */
  private function connect()
  {
    if( $this->database != static::$current_database )
    {
      if( false == $this->connection->Connect(
        $this->server, $this->username, $this->password, $this->database ) )
        throw new \sabretooth\exception\runtime(
          "Unable to connect to the '$database' database.", __METHOD__ );
      static::$current_database = $this->database;
    }
  }

  /**
   * Holds all table column types in an associate array where table => ( column => type )
   * This member is defined on demand, not when the class is created or a
   * record is loaded.
   * @var array
   * @access private
   */
  private $columns = array();

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
}
?>
