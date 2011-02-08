<?php
/**
 * active_record.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\database
 * @filesource
 */

namespace sabretooth\database;

/**
 * active_record: abstract database table object
 *
 * The active_record class represents tables in the database.  Each table has its own class which
 * extends this class.  Furthermore, each table must have a single 'id' column as its primary key.
 * @package sabretooth\database
 */
abstract class active_record extends \sabretooth\base_object
{
  /**
   * Constructor
   * 
   * The constructor either creates a new object which can then be insert into the database by
   * calling the {@link save} method, or, if an primary key is provided then the row with the
   * requested primary id will be loaded.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @throws exception\database
   * @param integer $id The primary key for this object.
   * @access public
   */
  public function __construct( $id = NULL )
  {
    // determine the columns for this table
    $db = \sabretooth\session::self()->get_db();
    $columns = $db->MetaColumnNames( static::get_table_name() );

    if( !is_array( $columns ) || 0 == count( $columns ) )
      throw new \sabretooth\exception\database(
        "Meta column names return no columns for table ".static::get_table_name() );

    foreach( $columns as $name ) $this->columns[ $name ] = NULL;
    
    if( NULL != $id )
    {
      // make sure this table has an id column as the primary key
      $primary_key_names = $db->MetaPrimaryKeys( static::get_table_name() );
      if( 1 != count( $primary_key_names ) || 'id' != $primary_key_names[0] )
      {
        throw new \sabretooth\exception\database(
          'Unable to create record, single-column primary key "id" does not exist.' );
      }
      $this->columns['id'] = intval( $id );
    }
    
    // now load the data from the database
    // (this gets skipped if a primary key has not been set)
    $this->load();
  }
  
  /**
   * Destructor
   * 
   * The destructor will save the record to the database if auto-saving is on and the record
   * already exists in the database (new records must explicitely be saved to be added to the
   * database).
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function __destruct()
  {
    // save to the database if auto-saving is on
    if( self::$auto_save && isset( $this->columns['id'] ) ) $this->save();
  }
  
  /**
   * Loads the active record from the database.
   * 
   * If this is a new record then this method does nothing, if the record's primary key is set then
   * the data from the corresponding row is loaded.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function load()
  {
    if( isset( $this->columns['id'] ) )
    {
      $row = self::get_row(
        sprintf( 'SELECT * FROM %s WHERE id = %d',
                 static::get_table_name(),
                 $this->id ) );

      if( 0 == count( $row ) )
        throw new \sabretooth\exception\database( 'Load failed to find record.', $sql );

      $this->columns = $row;
    }
  }
  
  /**
   * Saves the active record to the database.
   * 
   * If this is a new record then a new row will be inserted, if not then the row with the
   * corresponding id will be updated.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @throws exception\database
   * @access public
   */
  public function save()
  {
    // building the SET list since it is identical for inserts and updates
    $sets = '';
    $first = true;
    foreach( $this->columns as $key => $val )
    {
      if( 'id' != $key )
      {
        $sets .= sprintf( '%s %s = %d',
                          $first ? '', ',',
                          $key,
                          self::format_string( $val ) );
        $first = false;
      }
    }
    
    // either insert or update the row based on whether the primary key is set
    self::execute(
      sprintf( is_null( $this->columns['id'] )
        ? 'INSERT INTO %s SET %s'
        : 'UPDATE %s SET %s WHERE id = %d',
        static::get_table_name(),
        $sets,
        $this->columns['id'] )  );

    // get the new new primary key
    if( is_null( $this->columns['id'] ) ) $this->columns['id'] = self::insert_id();
  }

  /**
   * Count the total number of rows in the table.
   *
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return int
   * @static
   * @access public
   */
  public static function count()
  {
    return self::get_one(
      sprintf( 'SELECT COUNT(*) FROM %s',
               static::get_table_name() ) );
  }

  /**
   * Magic get method.
   *
   * Magic get method which returns the column value from the record's table
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $column_name The name of the column or table being fetched from the database
   * @return mixed
   * @access public
   */
  public function __get( $column_name )
  {
    // if we get here then $column_name isn't a column === BAD CODE
    if( !$this->has_column_name( $column_name ) )
      throw new \sabretooth\exception\database(
        sprintf( 'Table %s does not have a column named "%s%".',
                 static::get_table_name(),
                 $column_name ) );
    
    return isset( $this->columns[ $column_name ] ) ? $this->columns[ $column_name ] : NULL;
  }

  /**
   * Magic set method.
   *
   * Magic set method which sets the column value to a record's table.
   * For this change to be writen to the database see the {@link save} method
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $column_name The name of the column
   * @param mixed $value The value to set the contents of a column to
   * @access public
   */
  public function __set( $column_name, $value )
  {
    // if we get here then $column_name isn't a column === BAD CODE
    if( !$this->has_column_name( $column_name ) )
      throw new \sabretooth\exception\database(
        sprintf( 'Table %s does not have a column named "%s%".',
                 static::get_table_name(),
                 $column_name ) );
    
    $this->columns[ $column_name ] = $value;
  }
  
  /**
   * Magic call method.
   * 
   * Magic call method which allows for get_<record>() and get_<record>_list() to be called on
   * objects where a foreign key or joining "has" table exist, respectively.
   * For instance, if a table has a foreign key other_id, then get_other() will return the
   * "other" record for the primary key other_id.
   * If a table has a joining "has", table this_has_that, then calling get_that_list() from a
   * "this" record will return a list (array) of "that" recrods.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $name The name of the called function (should be get_<record> or
   *                     get_<record>_list(), where <record> is the name of an active record class
   * @param array $args The arguments passed to the called function.
   * @return active_record|array(active_record)
   * @access public
   */
  public function __call( $name, $args )
  {
    $invalid_method = false;
    $return_value = NULL;

    // check to make sure the function name is appropriate
    if( 'get_' != substr( $name, 0, 4 ) )
    {
      $invalid_method = true;
    }
    else
    {
      // make sure the refering table exists
      $name_parts = explode( '_', $name );
      $foreign_table_name = $name_parts[1];
      $primary_key_name = static::get_table_name().'_id';
      $foreign_key_name = $foreign_table_name.'_id';
      if( !self::table_exists( $name_parts[1] ) )
      {
        trigger_error(
          sprintf( 'Call to %s::%s() references invalid table "%s".',
                   static::get_class_name(),
                   $name,
                   $foreign_table_name ),
          E_USER_ERROR );
      }
      
      if( 2 == count( $name_parts ) )
      { // we're linking a foreign key
        // make sure this table has the correct foreign key
        if( !$this->has_column_name( $foreign_key_name ) )
        {
          trigger_error(
            sprintf( 'Call to %s::%s() references missing foreign key "%s".',
                     static::get_class_name(),
                     $name,
                     $foreign_key_name ),
            E_USER_ERROR );
        }

        // create the record using the foreign key
        $return_value = new $foreign_table_name( $this->$foreign_key_name );
      }
      else if( 3 == count( $name_parts ) && 'list' == $name_parts[2] )
      { // we're linking a joining table
        // make sure joining table exists
        $joining_table_name = static::get_table_name().'_has_'.$foreign_table_name;
        if( !$this->table_exists( $joining_table_name ) )
        {
          trigger_error(
            sprintf( 'Call to %s::%s() references missing joining table "%s".',
                     static::get_class_name(),
                     $name,
                     $joining_table_name ),
            E_USER_ERROR );
        }

        $ids = self::get_col(
          sprintf( 'SELECT %s FROM %s WHERE %s = %d',
                   $foreign_key_name,
                   $joining_table_name,
                   $primary_key_name,
                   $this->id ) );

        $return_value = array();
        foreach( $ids as $id ) array_push( $return_value, new $foreign_table_name( $id ) );
      }
      else
      {
        $invalid_method = true;
      }
    }

    if( $invalid_method )
    {
      trigger_error(
        sprintf( 'Call to undefined function: %s::%s().',
                 static::get_class_name(),
                 $name ),
        E_USER_ERROR );
    }

    return $return_value;
  }

  /**
   * Select a number of records.
   * 
   * This method returns an array of records.
   * Be careful when calling this method.  Based on the count and offset parameters an object is
   * created for every row being selected, so selecting a very large number of rows (1000+) isn't
   * a good idea.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param int $count The number of records to return
   * @param int $offset The 0-based index of the first record to start selecting from
   * @param string $sort_column Which column to sort by during the select.
   * @param boolean $descending Whether to sort descending or ascending.
   * @param array $restrictions And array of restrictions to add to the were clause of the select.
   * @return array( active_record )
   * @static
   * @access public
   */
  public static function select(
    $count = 0, $offset = 0, $sort_column = NULL, $descending = false, $restrictions = NULL )
  {
    $records = array();
    
    // build the restriction list
    $where = '';
    if( is_array( $restrictions ) && 0 < count( $restrictions ) )
    {
      $first = true;
      $where = 'WHERE ';
      foreach( $restrictions as $column => $value )
      {
        $where .= sprintf( '%s %s = %d',
                           $first ? '', 'AND',
                           $column,
                           self::format_string( $value ) );
        $first = false;
      }
    }
    
    // build the order
    $order = '';
    if( !is_null( $sort_column ) )
    {
      $order = sprintf( 'ORDER BY %s %s',
                        $sort_column,
                        $descending ? 'DESC' : '' );
    }

    // build the limit
    $limit = '';
    if( 0 < $count )
    {
      $limit = sprintf( 'LIMIT %d OFFSET %d',
                        $count,
                        $offset );
    }
    
    $id_list = self::get_col(
      sprintf( 'SELECT id FROM %s %s %s %s',
               static::get_table_name(),
               $where,
               $order,
               $limit ) );

    foreach( $id_list as $id ) array_push( $records, new static( $id ) );

    return $records;
  }

  /**
   * Get record using unique key.
   * 
   * This method returns an instance of the active record using the name and value of a unique key.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $column a column with the unique key property
   * @param string $value the value of the column to match
   * @return database\active_record
   * @static
   * @access public
   */
  public static function get_unique_record( $column, $value )
  {
    $record = NULL;
    $database = \sabretooth\session::self()->get_setting( 'db', 'database' );

    // determine the unique key(s)
    $unique_keys = self::get_col( 
      sprintf( 'SELECT COLUMN_NAME '.
               'FROM information_schema.COLUMNS '.
               'WHERE TABLE_SCHEMA = %s '.
               'AND TABLE_NAME = %s '.
               'AND COLUMN_KEY = "UNI"',
               self::format_string( $database ),
               self::format_string( static::get_table_name ) ) );
    
    // make sure the column is unique
    if( in_array( $column, $unique_keys ) )
    {
      // this returns null if no records are found
      $id = self::get_one(
        sprintf( 'SELECT id FROM %s WHERE %s = %s',
                 static::get_table_name(),
                 $column,
                 self::format_string( $value ) ) );

      if( !is_null( $id ) ) $record = new static( $id );
    }
    return $record;
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
    return is_null( $string ) ? 'NULL' : '"'.mysql_real_escape_string( $string ).'"';
  }

  /**
   * Returns the name of the table associated with this active record.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return string
   * @access protected
   */
  protected static function get_table_name()
  {
    // table and class names should always be identical
    return self::get_class_name();
  }
  
  /**
   * Determines whether a particular table exists.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $name The name of the table to check for.
   * @return boolean
   * @access protected
   */
  protected static function table_exists( $name )
  {
    $database = \sabretooth\session::self()->get_setting( 'db', 'database' );
    $count = self::get_one(
      sprintf( 'SELECT COUNT(*) '.
               'FROM information_schema.TABLES '.
               'WHERE Table_Name = %s '.
               'AND TABLE_SCHEMA = %s',
               self::format_string( $name ),
               self::format_string( $database ) ) );

    return 0 < $count;
  }

  /**
   * Returns whether the record's associated table has a specific column name.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $name A column name
   * @return boolean
   * @access protected
   */
  protected function has_column_name( $column_name )
  {
    return array_key_exists( $column_name, $this->columns );
  }
  
  /**
   * Database convenience method.
   * 
   * Execute SQL statement $sql and return derived class of ADORecordSet if successful. Note that a
   * record set is always returned on success, even if we are executing an insert or update
   * statement.
   * Note: This is a convenience wrapper for ADOdb::Execute()
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $sql SQL statement
   * @param array $input_array binding variables to parameters
   * @return ADORecordSet
   * @throws exception\database
   * @static
   * @access protected
   */
  protected static function execute( $sql, $input_array = false )
  {
    $db = \sabretooth\session::self()->get_db();
    $result = $db->Execute( $sql, $input_array );
    if( false === $result ) throw new \sabretooth\exception\database( $db->ErrorMsg(), $sql );
    return $result;
  }
  
  /**
   * Database convenience method.
   * 
   * Executes the SQL and returns the first field of the first row.
   * Note: This is a convenience wrapper for ADOdb::GetOne()
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $sql SQL statement
   * @param array $input_array binding variables to parameters
   * @return native or NULL if no records were found.
   * @throws exception\database
   * @static
   * @access protected
   */
  protected static function get_one( $sql, $input_array = false )
  {
    $db = \sabretooth\session::self()->get_db();
    $result = $db->GetOne( $sql, $input_array );
    if( false === $result ) throw new \sabretooth\exception\database( $db->ErrorMsg(), $sql );
    return $result;
  }
  
  /**
   * Database convenience method.
   * 
   * Executes the SQL and returns the first row as an array.
   * Note: This is a convenience wrapper for ADOdb::GetRow()
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $sql SQL statement
   * @param array $input_array binding variables to parameters
   * @return array (empty if no records are found)
   * @throws exception\database
   * @static
   * @access protected
   */
  protected static function get_row( $sql, $input_array = false )
  {
    $db = \sabretooth\session::self()->get_db();
    $result = $db->GetRow( $sql, $input_array );
    if( false === $result ) throw new \sabretooth\exception\database( $db->ErrorMsg(), $sql );
    return $result;
  }
  
  /**
   * Database convenience method.
   * 
   * Executes the SQL and returns the all the rows as a 2-dimensional array.
   * Note: This is a convenience wrapper for ADOdb::GetAll()
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $sql SQL statement
   * @param array $input_array binding variables to parameters
   * @return array (empty if no records are found)
   * @throws exception\database
   * @static
   * @access protected
   */
  protected static function get_all( $sql, $input_array = false )
  {
    $db = \sabretooth\session::self()->get_db();
    $result = $db->GetAll( $sql, $input_array );
    if( false === $result ) throw new \sabretooth\exception\database( $db->ErrorMsg(), $sql );
    return $result;
  }
  
  /**
   * Database convenience method.
   * 
   * Executes the SQL and returns all elements of the first column as a 1-dimensional array.
   * Note: This is a convenience wrapper for ADOdb::GetCol()
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $sql SQL statement
   * @param array $input_array binding variables to parameters
   * @param boolean $trim determines whether to right trim CHAR fields
   * @return array (empty if no records are found)
   * @throws exception\database
   * @static
   * @access protected
   */
  protected static function get_col( $sql, $input_array = false, $trim = false )
  {
    $db = \sabretooth\session::self()->get_db();
    $result = $db->GetCol( $sql, $input_array, $trim );
    if( false === $result ) throw new \sabretooth\exception\database( $db->ErrorMsg(), $sql );
    return $result;
  }
  
  /**
   * Database convenience method.
   * 
   * Returns the last autonumbering ID inserted.
   * Note: This is a convenience wrapper for ADOdb::Insert_ID()
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return int
   * @static
   * @access protected
   */
  protected static function insert_id()
  {
    return \sabretooth\session::self()->get_db()->Insert_ID();
  }
  
  /**
   * Database convenience method.
   * 
   * Returns the number of rows affected by a update or delete statement.
   * Note: This is a convenience wrapper for ADOdb::Affected_Rows()
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return int
   * @static
   * @access protected
   */
  protected static function affected_rows( $sql, $input_array = false, $trim = false )
  {
    return \sabretooth\session::self()->get_db()->Affected_Rows();
  }
  
  /**
   * Holds all table column values in an associate array where key=>value is
   * column_name=>column_value
   * @var array
   * @access private
   */
  private $columns = array();

  /**
   * Determines whether or not to write changes to the database on deletion.
   * This value affects ALL active records.
   * @var boolean
   * @static
   * @access public
   */
  public static $auto_save = false;
}
?>
