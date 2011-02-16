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
 * TODO: Maybe a modifier should be added as a member to the active_record class so there is less
 *       passing around going on.
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
   * @param integer $id The primary key for this object.
   * @throws exception\runtime
   * @access public
   */
  public function __construct( $id = NULL )
  {
    // determine the columns for this table
    $db = \sabretooth\session::self()->get_db();
    $columns = $db->MetaColumnNames( self::get_table_name() );

    if( !is_array( $columns ) || 0 == count( $columns ) )
      throw new \sabretooth\exception\runtime(
        "Meta column names return no columns for table ".self::get_table_name(), __METHOD__ );

    foreach( $columns as $name ) $this->columns[ $name ] = NULL;
    
    if( NULL != $id )
    {
      // make sure this table has an id column as the primary key
      $primary_key_names = $db->MetaPrimaryKeys( self::get_table_name() );
      if( 1 != count( $primary_key_names ) || 'id' != $primary_key_names[0] )
      {
        throw new \sabretooth\exception\runtime(
          'Unable to create record, single-column primary key "id" does not exist.', __METHOD__ );
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
   * @throws exception\runtime
   * @access public
   */
  public function load()
  {
    if( isset( $this->columns['id'] ) )
    {
      $sql = sprintf( 'SELECT * FROM %s WHERE id = %d',
                      self::get_table_name(),
                      $this->id );

      $row = self::get_row( $sql );

      if( 0 == count( $row ) )
        throw new \sabretooth\exception\runtime( 'Load failed to find record.', $sql, __METHOD__ );

      $this->columns = $row;
    }
  }
  
  /**
   * Saves the active record to the database.
   * 
   * If this is a new record then a new row will be inserted, if not then the row with the
   * corresponding id will be updated.
   * @author Patrick Emond <emondpd@mcmaster.ca>
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
        $sets .= sprintf( '%s %s = %s',
                          $first ? '' : ',',
                          $key,
                          self::format_string( $val ) );
        $first = false;
      }
    }
    
    // either insert or update the row based on whether the primary key is set
    $sql = sprintf( is_null( $this->columns['id'] )
                    ? 'INSERT INTO %s SET %s'
                    : 'UPDATE %s SET %s WHERE id = %d',
                    self::get_table_name(),
                    $sets,
                    $this->columns['id'] );
    self::execute( $sql );
    
    // get the new new primary key
    if( is_null( $this->columns['id'] ) ) $this->columns['id'] = self::insert_id();
  }
  
  /**
   * Deletes the record.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function delete()
  {
    if( is_null( $this->id ) )
    {
      \sabretooth\log::warning( 'Tried to delete record with no id.' );
      return;
    }
    
    $sql = sprintf( 'DELETE FROM %s WHERE id = %s',
                    self::get_table_name(),
                    $this->columns['id'] );
    self::execute( $sql );
  }

  /**
   * Count the total number of rows in the table.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the count.
   * @return int
   * @static
   * @access public
   */
  public static function count( $modifier )
  {
    return self::get_one(
      sprintf( 'SELECT COUNT(*) FROM %s %s',
               self::get_table_name(),
               is_null( $modifier ) ? '' : $modifier->get_sql() ) );
  }

  /**
   * Magic get method.
   *
   * Magic get method which returns the column value from the record's table
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $column_name The name of the column or table being fetched from the database
   * @return mixed
   * @throws exception\argument
   * @access public
   */
  public function __get( $column_name )
  {
    // make sure the column exists
    if( !$this->has_column_name( $column_name ) )
      throw new \sabretooth\exception\argument( 'column_name', $column_name, __METHOD__ );
    
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
   * @throws exception\argument
   * @access public
   */
  public function __set( $column_name, $value )
  {
    // make sure the column exists
    if( !$this->has_column_name( $column_name ) )
      throw new \sabretooth\exception\argument( 'column_name', $column_name, __METHOD__ );
    
    $this->columns[ $column_name ] = $value;
  }
  
  /**
   * Magic call method.
   * 
   * Magic call method which allows for several methods which get information about records in
   * tables linked to by this table by either a foreign key or joining table.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $name The name of the called function (should be get_<record>,
   *                     get_<record>_count() or get_<record>_list(), where <record> is the name
   *                     of an active record class related to this record.
   * @param array $args The arguments passed to the called function.  This can either be null or
   *                    a modifier to be applied to the magic methods.
   * @throws exception\runtime, exception\argument
   * @return mixed
   * @access public
   * @method array get_<record>() Returns the record with foreign keys referencing the <record>
   *               table.  For instance, if a record has a foreign key "other_id", then
   *               get_other() will return the "other" record with the id equal to other_id.
   * @method array get_record_list() Returns an array of records from the joining <record> table
   *               given the provided modifier.  If a record has a joining "has" table then
   *               calling get_other_list() will return an array of "other" records which are
   *               linked in the joining table, and get_other_count() will return the number of
   *               "other" recrods found in the joining table.
   * @method array get_record_list_inverted() This is the same as the non-inverted method but it
   *               returns all items which are NOT linked to joining table.
   * @method int get_<record>_count() Returns the number of records in the joining <record> table
   *             given the provided modifier.
   * @method int get_<record>_count_inverted() This is the same as the non-inverted method but it
   *             returns the number of records NOT in the joining table.
   * @method null add_<record>() Given an array of ids, this method adds associations between the
   *              current and foreign <record> by adding rows into the joining "has" table.
   * @method null remove_<record>() Given an array of ids, this method removes associations between
   *              the current and foreign <record> by removing rows from the joining "has" table.
   */
  public function __call( $name, $args )
  {
    // create an exception which will be thrown if anything bad happens
    $exception = new \sabretooth\exception\runtime(
      sprintf( 'Call to undefined function: %s::%s().',
               get_called_class(),
               $name ), __METHOD__ );

    $return_value = NULL;

    // parse the function call name
    $name_parts = explode( '_', $name );
    
    // must have between 2 and 4 parts
    if( 2 > count( $name_parts ) || 4 < count( $name_parts ) ) throw $exception;
    
    // first part is the action, second is the foreign table name
    if( 2 <= count( $name_parts ) )
    {
      // make sure action is valid
      $action = $name_parts[0];
      if( 'get' != $action && 'add' != $action && 'remove' != $action ) throw $exception;

      // make sure the foreign table exists
      $foreign_table_name = $name_parts[1];
      if( !self::table_exists( $foreign_table_name ) ) throw $exception;

      // add and remove require a joining table
      $joining_table_name = 'add' == $action || 'remove' == $action
                          ? self::get_table_name().'_has_'.$foreign_table_name
                          : NULL;

      $primary_key_name = self::get_table_name().'_id';
      $foreign_key_name = $foreign_table_name.'_id';
      $sub_action = NULL;
    }
    
    if( 3 <= count( $name_parts ) )
    {
      // make sure sub action is valid
      $sub_action = $name_parts[2];
      if( 'list' != $sub_action && 'count' != $sub_action ) throw $exception;
      
      $joining_table_name = self::get_table_name().'_has_'.$foreign_table_name;
      
      // define the modifier
      $modifier = 1 == count( $args ) &&
                  'sabretooth\\database\\modifier' == get_class( $args[0] )
                ? $args[0]
                : new modifier();

      $inverted = false;
    }
     
    // if we're using one, make sure the joining table exists
    if( !is_null( $joining_table_name ) )
      if( !$this->table_exists( $joining_table_name ) ) throw $exception;

    if( 4 == count( $name_parts ) )
    {
      if( 'inverted' == $name_parts[3] ) $inverted = true;
      else throw $exception;
    }

    // once we get here we know for sure that the function name is valid
    if( 'add' == $action )
    { // calling: add_<record>( $ids )
      // make sure the first argument is a non-empty array of ids
      if( 1 != count( $args ) || !is_array( $args[0] ) || 0 == count( $args[0] ) )
        throw new \sabretooth\exception\argument( 'args', $args, __METHOD__ );
      $id_list = $args[0];
      
      $values = '';
      $first = true;
      foreach( $id_list as $id )
      {
        if( !$first ) $values .= ', ';
        $values .= sprintf( '(%s, %s)',
                         active_record::format_string( $this->id ),
                         active_record::format_string( $id ) );
        $first = false;
      }

      self::execute(
        sprintf( 'INSERT INTO %s (%s, %s) VALUES %s',
                 $joining_table_name,
                 $primary_key_name,
                 $foreign_key_name,
                 $values ) );
    }
    else if( 'remove' == $action )
    { // calling: remove_<record>( $ids )
      // make sure the first argument is a non-empty array of ids
      if( 1 != count( $args ) || !is_array( $args ) || 0 == count( $args ) )
        throw new \sabretooth\exception\argument( 'args', $args, __METHOD__ );
      $id_list = $args[0];
      
      $in = '';
      $first = true;
      foreach( $id_list as $id )
      {
        if( !$first ) $in .= ', ';
        $in .= active_record::format_string( $id );
        $first = false;
      }

      self::execute(
        sprintf( 'DELETE FROM %s WHERE %s = %s AND %s IN( %s )',
                 $joining_table_name,
                 $primary_key_name,
                 $this->id,
                 $foreign_key_name,
                 $in ) );
    }
    else if( 'get' == $action && is_null( $sub_action ) )
    { // calling: get_<record>()
      // make sure this table has the correct foreign key
      if( !$this->has_column_name( $foreign_key_name ) ) throw $exception;

      // create the record using the foreign key
      $class_name = '\\sabretooth\\database\\'.$foreign_table_name;
      $return_value = new $class_name( $this->$foreign_key_name );
    }
    else if( 'get' == $action && !is_null( $sub_action ) )
    { // calling one of: get_<record>_list( $modifier = NULL )
      //                 get_<record>_list_inverted( $modifier = NULL )
      //                 get_<record>_count( $modifier = NULL )
      //                 get_<record>_count_inverted( $modifier = NULL )
      if( $inverted )
      { // we need to invert the list
        // first create SQL to match all records in the joining table
        $sub_modifier = new modifier();
        $sub_modifier->where( $primary_key_name, $this->id );
        $sub_select_sql =
          sprintf( 'SELECT %s FROM %s %s',
                   $foreign_key_name,
                   $joining_table_name,
                   $sub_modifier->get_sql() );
        // now create SQL that gets all IDs that is NOT in that list
        $modifier->where_not_in( 'id', $sub_select_sql, false );
        $sql = sprintf( 'SELECT %s FROM %s %s',
                        'list' == $sub_action ? 'id' : 'COUNT( id )',
                        $foreign_table_name,
                        $modifier->get_sql() );
      }
      else
      { // no inversion, just select the records from the joining table
        $modifier->where( $primary_key_name, $this->id );
        $sql = sprintf( 'SELECT %s FROM %s',
                        'list' == $sub_action
                          ? $foreign_key_name
                          : 'COUNT( '.$foreign_key_name.' )',
                        $joining_table_name,
                        $modifier->get_sql() );
      }
      
      if( 'list' == $sub_action )
      {
        $ids = self::get_col( $sql );
        $return_value = array();
        $class_name = '\\sabretooth\\database\\'.$foreign_table_name;
        foreach( $ids as $id ) array_push( $return_value, new $class_name( $id ) );
      }
      else // 'count' == $sub_action
      {
        $return_value = self::get_one( $sql );
      }
    }

    return $return_value;
  }

  /**
   * Select a number of records.
   * 
   * This method returns an array of records.
   * The modifier may include any columns from tables which this record has a foreign key
   * relationship with.  To sort by such columns make sure to include the table name along with
   * the column name (for instance 'table.column') as the sort column value.
   * Be careful when calling this method.  Based on the modifier object a record is created for
   * every row being selected, so selecting a very large number of rows (100+) isn't a good idea.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the selection.
   * @return array( active_record )
   * @static
   * @access public
   */
  public static function select( $modifier = NULL )
  {
    $records = array();
    $this_table = self::get_table_name();
    
    // check to see if the modifier is sorting a value in a foreign table
    $table_list = array( $this_table );
    if( !is_null( $modifier ) )
    {
      foreach( $modifier->get_order_columns() as $order )
      {
        $table = strstr( $order, '.', true );
        if( $table && 0 < strlen( $table ) && $table != $this_table )
        {
          // check to see if we have a foreign key for this table
          $temp_record = new static();
          $foreign_key_name = $table.'_id';
          if( $temp_record->has_column_name( $foreign_key_name ) )
          {
            // add the table to the list to select and join it in the modifier
            array_push( $table_list, $table );
            $modifier->where( $this_table.'.'.$foreign_key_name, $table.'.id', false );
          }
        }
      }
    }
    
    // build the table list
    $select_tables = '';
    $first = true;
    foreach( $table_list as $table )
    {
      $select_tables .= sprintf( '%s %s',
                                 $first ? '' : ',',
                                 $table );
      $first = false;
    }

    $id_list = self::get_col(
      sprintf( 'SELECT %s.id FROM %s %s',
               $this_table,
               $select_tables,
               is_null( $modifier ) ? '' : $modifier->get_sql() ) );

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
               self::format_string( self::get_table_name() ) ) );
    
    // make sure the column is unique
    if( in_array( $column, $unique_keys ) )
    {
      // this returns null if no records are found
      $id = self::get_one(
        sprintf( 'SELECT id FROM %s WHERE %s = %s',
                 self::get_table_name(),
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
    // table and class names (without namespaces) should always be identical
    return substr( strrchr( get_called_class(), '\\' ), 1 );
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
   * @access public
   */
  public function has_column_name( $column_name )
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
    if( false === $result )
    {
      // pass the db error code instead of a class error code
      throw new \sabretooth\exception\database( $db->ErrorMsg(), $sql, $db->ErrorNo() );
    }

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
    if( false === $result )
    {
      // pass the db error code instead of a class error code
      throw new \sabretooth\exception\database( $db->ErrorMsg(), $sql, $db->ErrorNo() );
    }

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
    if( false === $result )
    {
      // pass the db error code instead of a class error code
      throw new \sabretooth\exception\database( $db->ErrorMsg(), $sql, $db->ErrorNo() );
    }

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
    if( false === $result )
    {
      // pass the db error code instead of a class error code
      throw new \sabretooth\exception\database( $db->ErrorMsg(), $sql, $db->ErrorNo() );
    }

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
    if( false === $result )
    {
      // pass the db error code instead of a class error code
      throw new \sabretooth\exception\database( $db->ErrorMsg(), $sql, $db->ErrorNo() );
    }

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
