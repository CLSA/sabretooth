<?php
/**
 * active_record.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\database
 */

namespace sabretooth\database;

/**
 * active_record: abstract database table object
 *
 * The active_record class represents tables in the database.  Each table has its own class which
 * extends this class.
 * @package sabretooth\database
 */
abstract class active_record extends \sabretooth\base_object
{
  /**
   * Constructor
   * 
   * The constructor either creates a new object which can then be insert into the database by
   * calling the {@link save} method, or, if an $primary_keys is provided then the row with the
   * primary id(s) equal to $primary_keys will be loaded.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @throws exception\database
   * @param integer|array $primary_keys The primary id value for this object (use an associative
   *                                  array if there are multiple primary keys)
   * @access public
   */
  public function __construct( $primary_keys = NULL )
  {
    //\sabretooth\log::debug( 'active_record: '.( is_null( $primary_keys )
    //  ? 'creating new '.$this->get_table_name().' record'
    //  : 'creating '.$this->get_table_name().' with primary key(s) '.$primary_keys ) );
    
    // determine the columns for this table
    $db = \sabretooth\session::self()->get_db();
    $columns = $db->MetaColumnNames( static::get_table_name() );
    assert( is_array( $columns ) );
    foreach( $columns as $name ) $this->columns[ $name ] = NULL;
    
    // validate the primary key (if there is one)
    $primary_key_names = static::get_primary_key_names();
    if( NULL != $primary_keys )
    {
      if( is_numeric( $primary_keys ) )
      {
        if( 1 != count( $primary_key_names ) || 'id' != $primary_key_names[0] )
        {
          throw new \sabretooth\exception\database(
            'Unable to create record, primary key "id" does not exist.' );
        }
        $this->columns[ $primary_key_names[0] ] = intval( $primary_keys );
      }
      else if( is_array( $primary_keys ) )
      {
        if( count( $primary_key_names ) != count( $primary_keys ) )
        {
          throw new \sabretooth\exception\database(
            'Unable to create record, wrong number of primary keys ('.count( $primary_keys ).
            ' provided, '.count( $primary_key_names ).' required.' );
        }
        else
        {
          foreach( $primary_key_names as $primary_key_name )
          {
            if( !in_array( $primary_key_name, array_keys( $primary_keys ) ) )
            {
              throw new \sabretooth\exception\database(
                'Unable to create record, missing primary key "'.$primary_key_name.'".' );
            }

            // populate the primary key value
            $this->columns[ $primary_key_name ] = $primary_keys[ $primary_key_name ];
          }
        }
      }
      else
      {
        throw new \sabretooth\exception\database(
          'Unable to create record, primary keys are invalid.' );
      }
    }
    
    // now load the data from the database using the current primary keys
    // (this is skipped if no primary key was provided)
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
    if( self::$auto_save && !is_null( $this->columns['id'] ) )
    {
      $this->save();
    }
  }
  
  /**
   * Loads the active record from the database.
   * 
   * If this is a new record then this method does nothing, if the record is associated with a
   * primary key then the data from the corresponding row is loaded.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function load()
  {
    $missing_primary_keys = false;

    $where = '';
    $first = true;
    $primary_key_names = static::get_primary_key_names();
    foreach( $primary_key_names as $primary_key_name )
    {
      if( !is_null( $this->columns[ $primary_key_name ] ) )
      {
        $where .= ( $first ? '' : ' AND ' ).
                  $primary_key_name.' = "'.$this->columns[ $primary_key_name ].'"';
        $first = false;
      }
      else
      {
        $missing_primary_keys = true;
        break;
      }
    }
    
    // only select if the primary keys are set, otherwise this is a new record
    if( !$missing_primary_keys )
    {
      $sql = 'SELECT * '.
             'FROM '.static::get_table_name().' '.
             'WHERE '.$where;
      $row = self::get_row( $sql );
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
    $primary_key_names = static::get_primary_key_names();

    // building the SET list since it is identical for inserts and updates
    $sets = '';
    $first = true;
    foreach( $this->columns as $key => $val )
    {
      if( !in_array( $key, $primary_key_names ) )
      {
        $sets .= ( $first ? "": ", " )."$key = '".self::format_string( $val )."'";
        $first = false;
      }
    }
    
    // determine if we have any missing primary keys
    $missing_primary_keys = false;
    foreach( $primary_key_names as $primary_key_name )
    {
      if( NULL == $this->column[ $primary_key_name ] )
      {
        $missing_primary_keys = true;
        break;
      }
    }

    // make sure we either have all primary keys, or a single null primary key (new row)
    // (we cannot automatically add new rows for tables with multiple primary keys)
    if( !$missing_primary_keys || ( 1 == count( $primary_key_names ) ) )
    {
      $sql = is_null( $this->columns['id'] )
           // insert a new row
           ? 'INSERT INTO '.static::get_table_name().' '.
             'SET '.$sets
           // update an existing row
           : 'UPDATE '.static::get_table_name().' '.
             'SET '.$sets.' '.
             'WHERE id = '.$this->columns['id'];
      self::execute( $sql );

      // if we have a single primary key and it was null before, then it must be an auto-increment
      if( 1 == count( $primary_key_names ) &&
          'id' == $primary_key_names[0] &&
          is_null( $this->columns['id'] ) ) $this->columns['id'] = self::insert_id();
    }
    else
    {
      throw new \sabretooth\exception\database(
        'Unable to save record, one or more primary keys are missing.' );
    }
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
      'SELECT COUNT(*) '.
      'FROM '.static::get_table_name() );
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
    assert( $this->has_column_name( $column_name ) );
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
   * @access protected
   */
  public function __set( $column_name, $value )
  {
    // if we get here then $column_name isn't a column === BAD CODE
    assert( $this->has_column_name( $column_name ) );

    $this->columns[ $column_name ] = $value;
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
    return isset( $this->columns[ $column_name ] );
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
   * @return array( active_record )
   * @static
   * @access public
   */
  public static function select( $count = 0, $offset = 0, $sort_column = NULL )
  {
    $records = array();
    
    $primary_key_names = static::get_primary_key_names();
    $select = '';
    $first = true;
    foreach( $primary_key_names as $primary_key_name )
    {
      $select .= ( $first ? '' : ', ' ).$primary_key_name;
      $first = false;
    }

    $primary_ids_list = self::get_all(
      'SELECT '.$select.' '.
      'FROM '.static::get_table_name().' '.
      ( !is_null( $sort_column ) ? 'ORDER BY '.$sort_column.' ' : '' ).
      ( 0 < $count ? 'LIMIT '.$count.' OFFSET '.$offset : '' ) );

    foreach( $primary_ids_list as $primary_ids )
    {
      array_push( $records, new static( $primary_ids ) );
    }

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
    $db = \sabretooth\session::self()->get_db();

    // determine the unique key(s)
    $unique_keys = self::get_col( 
      'SELECT COLUMN_NAME '.
      'FROM information_schema.COLUMNS '.
      'WHERE TABLE_SCHEMA = "'.$database.'" '.
      'AND TABLE_NAME = "'.static::get_table_name().'" '.
      'AND COLUMN_KEY = "UNI"' );
    
    // make sure the column is unique
    if( in_array( $column, $unique_keys ) )
    {
      $primary_keys = $db->MetaPrimaryKeys( static::get_table_name() );

      // get the primary key(s) for this table matching the value
      $select = '';
      $first = true;
      foreach( $primary_keys as $primary_key_name )
      {
        $select .= ( $first ? '' : ', ' ).$primary_key_name;
      }
      
      // this returns an empty array if no records are found
      $primary_ids = self::get_row(
        'SELECT '.$select.' '.
        'FROM '.static::get_table_name().' '.
        'WHERE '.$column.' = "'.$value.'"' );
      
      if( count( $primary_ids ) )
      {
        // create a record using the selected primary key(s)
        $record = new static( $primary_ids );
      }
    }
    return $record;
  }

  /**
   * Returns the string formatted for database queries.
   * 
   * The returned value should be put in double quotes.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $string The string to format for use in a query.
   * @return string
   * @static
   * @access public
   */
  public static function format_string( $string )
  {
    // TODO: clean/escape the string before returning it
    return $string;
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
   * Returns a list of the names of all columns making up the primary key for this table.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return array( string )
   * @static
   * @access protected
   */
  protected static function get_primary_key_names()
  {
    return \sabretooth\session::self()->get_db()->MetaPrimaryKeys( static::get_table_name() );
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
   * @access public
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
   * @access public
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
   * @access public
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
   * @access public
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
   * @access public
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
   * @access public
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
   * @access public
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
