<?php
/**
 * record.class.php
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
 * record: abstract database table object
 *
 * The record class represents tables in the database.  Each table has its own class which
 * extends this class.  Furthermore, each table must have a single 'id' column as its primary key.
 * @package sabretooth\database
 */
abstract class record extends \sabretooth\base_object
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
    $columns = $this->get_column_names();

    if( !is_array( $columns ) || 0 == count( $columns ) )
      throw new exc\runtime(
        "No column names returned for table ".static::get_table_name(), __METHOD__ );
    
    // set the default value for all columns
    foreach( $columns as $name )
    {
      // If the default is CURRENT_TIMESTAMP, or if there is a DATETIME column by the name
      // 'start_time' then make the default the current date and time.
      // Because of mysql does not allow setting the default value for a DATETIME column to be
      // NOW() we need to set the default here manually
      $default = static::db()->get_column_default( static::get_table_name(), $name );
      if( 'CURRENT_TIMESTAMP' == $default || 
          ( 'start_time' == $name &&
            'datetime' == static::db()->get_column_data_type( static::get_table_name(), $name ) ) )
      {
        $this->column_values[$name] = date( 'Y-m-d H:i:s' );
      }
      else
      {
        $this->column_values[$name] = $default;
      }
    }
    
    if( NULL != $id )
    {
      // make sure this table has an id column as the primary key
      $primary_key_names = static::db()->meta_primary_keys( static::get_table_name() );
      if( 1 != count( $primary_key_names ) ||
          static::get_primary_key_name() != $primary_key_names[0] )
      {
        throw new exc\runtime(
          'Unable to create record, single-column primary key "'.
          static::get_primary_key_name().'" does not exist.', __METHOD__ );
      }
      $this->column_values[static::get_primary_key_name()] = intval( $id );
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
    if( self::$auto_save && isset( $this->column_values[static::get_primary_key_name()] ) )
      $this->save();
  }
  
  /**
   * Loads the record from the database.
   * 
   * If this is a new record then this method does nothing, if the record's primary key is set then
   * the data from the corresponding row is loaded.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @throws exception\runtime
   * @access public
   */
  public function load()
  {
    if( isset( $this->column_values[static::get_primary_key_name()] ) )
    {
      // not using a modifier here is ok since we're forcing id to be an integer
      $sql = sprintf( 'SELECT * FROM %s WHERE %s = %d',
                      static::get_table_name(),
                      static::get_primary_key_name(),
                      $this->column_values[static::get_primary_key_name()] );

      $row = static::db()->get_row( $sql );

      if( 0 == count( $row ) )
        throw new exc\runtime(
          sprintf( 'Load failed to find record for %s with %s = %d.',
                   static::get_table_name(),
                   static::get_primary_key_name(),
                   $this->column_values[static::get_primary_key_name()] ),
          __METHOD__ );

      $this->column_values = $row;

      // convert where necessary
      foreach( $this->column_values as $key => $val )
      {
        $column_type = static::db()->get_column_data_type( static::get_table_name(), $key );

        // convert where necessary
        if( 'datetime' == $column_type || 'timestamp' == $column_type )
        { // convert datetime to server date and time
          $this->column_values[$key] = util::from_server_date( $val );
        }
        elseif( 'time' == $column_type )
        { // convert time to server time
          $this->column_values[$key] = util::from_server_time( $val );
        }
      }
    }
  }
  
  /**
   * Saves the record to the database.
   * 
   * If this is a new record then a new row will be inserted, if not then the row with the
   * corresponding id will be updated.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @throws exception\runtime
   * @access public
   */
  public function save()
  {
    // warn if we are in read-only mode
    if( $this->read_only )
    {
      log::warning( 'Tried to save read-only record.' );
      return;
    }
    
    // if we have start_time and end_time (which can't be null), make sure end comes after start
    if( static::column_exists( 'start_time' ) &&
        static::column_exists( 'end_time' ) &&
        !is_null( static::db()->get_column_default( static::get_table_name(), 'end_time' ) ) )
    {
      $start_obj = new \DateTime( $this->start_time );
      $end_obj = new \DateTime( $this->end_time );
      $interval = $start_obj->diff( $end_obj );
      if( 0 != $interval->invert ||
        ( 0 == $interval->days && 0 == $interval->h && 0 == $interval->i && 0 == $interval->s ) )
      {
        throw new exc\runtime(
          'Tried to set end time which is not after the start time.', __METHOD__ );
      }
    }

    // building the SET list since it is identical for inserts and updates
    $sets = '';
    $first = true;
    foreach( $this->column_values as $key => $val )
    {
      if( static::get_primary_key_name() != $key )
      {
        $column_type = static::db()->get_column_data_type( static::get_table_name(), $key );

        // convert where necessary
        if( 'datetime' == $column_type || 'timestamp' == $column_type )
        { // convert datetime to server date and time
          $val = util::to_server_date( $val );
        }
        elseif( 'time' == $column_type )
        { // convert time to server time
          $val = util::to_server_time( $val );
        }
        
        $sets .= sprintf( '%s %s = %s',
                          $first ? '' : ',',
                          $key,
                          database::format_string( $val ) );
        $first = false;
      }
    }
    
    // either insert or update the row based on whether the primary key is set
    $sql = sprintf( is_null( $this->column_values[static::get_primary_key_name()] )
                    ? 'INSERT INTO %s SET %s'
                    : 'UPDATE %s SET %s WHERE %s = %d',
                    static::get_table_name(),
                    $sets,
                    static::get_primary_key_name(),
                    $this->column_values[static::get_primary_key_name()] );

    static::db()->execute( $sql );
    
    // get the new primary key
    if( is_null( $this->column_values[static::get_primary_key_name()] ) )
      $this->column_values[static::get_primary_key_name()] = static::db()->insert_id();
  }
  
  /**
   * Deletes the record.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function delete()
  {
    // warn if we are in read-only mode
    if( $this->read_only )
    {
      log::warning( 'Tried to delete read-only record.' );
      return;
    }

    // check the primary key value
    if( is_null( $this->column_values[static::get_primary_key_name()] ) )
    {
      log::warning( 'Tried to delete record with no id.' );
      return;
    }
    
    // not using a modifier here is ok since we're forcing id to be an integer
    $sql = sprintf( 'DELETE FROM %s WHERE %s = %d',
                    static::get_table_name(),
                    static::get_primary_key_name(),
                    $this->column_values[static::get_primary_key_name()] );
    static::db()->execute( $sql );
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
    if( !static::column_exists( $column_name ) )
      throw new exc\argument( 'column_name', $column_name, __METHOD__ );
    
    return isset( $this->column_values[ $column_name ] ) ?
      $this->column_values[ $column_name ] : NULL;
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
    if( !static::column_exists( $column_name ) )
      throw new exc\argument( 'column_name', $column_name, __METHOD__ );
    
    $this->column_values[ $column_name ] = $value;
  }
  
  /**
   * Magic call method.
   * 
   * Magic call method which allows for several methods which get information about records in
   * tables linked to by this table by either a foreign key or joining table.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $name The name of the called function (should be get_<record>,
   *                     get_<record>_count() or get_<record>_list(), where <record> is the name
   *                     of an record class related to this record.
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
   * @method null remove_<record>() Given an id, this method removes the association between the
                  current and foreign <record> by removing the corresponding row from the joining
                  "has" table.
   */
  public function __call( $name, $args )
  {
    // create an exception which will be thrown if anything bad happens
    $exception = new exc\runtime(
      sprintf( 'Call to undefined function: %s::%s().',
               get_called_class(),
               $name ), __METHOD__ );

    $return_value = NULL;
    
    // set up regular expressions
    $start = '/^add_|remove_|get_/';
    $end = '/(_list|_count)(_inverted)?$/';
    
    // see if the start of the function name is a match
    if( !preg_match( $start, $name, $match ) ) throw $exception;
    $action = substr( $match[0], 0, -1 ); // remove underscore

    // now get the subject by removing the start and end of the function name
    $subject = preg_replace( array( $start, $end ), '', $name );
    
    // make sure the foreign table exists
    if( !static::db()->table_exists( $subject ) ) throw $exception;
    
    if( 'add' == $action )
    { // calling: add_<record>( $ids )
      // make sure the first argument is a non-empty array of ids
      if( 1 != count( $args ) || !is_array( $args[0] ) || 0 == count( $args[0] ) )
        throw new exc\argument( 'args', $args, __METHOD__ );

      $ids = $args[0];
      $this->add_records( $subject, $ids );
      return;
    }
    else if( 'remove' == $action )
    { // calling: remove_<record>( $ids )
      // make sure the first argument is a non-empty array of ids
      if( 1 != count( $args ) || 0 >= $args[0] )
        throw new exc\argument( 'args', $args, __METHOD__ );

      $id = $args[0];
      $this->remove_record( $subject, $id );
      return;
    }
    else if( 'get' == $action )
    {
      // get the end of the function name
      $sub_action = preg_match( $end, $name, $match ) ? substr( $match[0], 1 ) : false;
      
      if( !$sub_action )
      {
        // calling: get_<record>()
        // make sure this table has the correct foreign key
        if( !static::column_exists( $subject.'_id' ) ) throw $exception;
        return $this->get_record( $subject );
      }
      else
      { // calling one of: get_<record>_list( $modifier = NULL )
        //                 get_<record>_list_inverted( $modifier = NULL )
        //                 get_<record>_count( $modifier = NULL )
        //                 get_<record>_count_inverted( $modifier = NULL )
  
        // if there is an argument, make sure it is a modifier
        if( 0 < count( $args ) &&
            !is_null( $args[0] ) &&
            is_object( $args[0] ) &&
            'sabretooth\\database\\modifier' != get_class( $args[0] ) )
          throw new exc\argument( 'args', $args, __METHOD );
        
        // determine the sub action and whether to invert the result
        $inverted = false;
        if( 'list' == $sub_action || 'count' == $sub_action ) {}
        else if( 'list_inverted' == $sub_action )
        {
          $sub_action = 'list';
          $inverted = true;
        }
        else if( 'count_inverted' == $sub_action )
        {
          $sub_action = 'count';
          $inverted = true;
        }
        else throw $exception;
        
        // execute the function
        $modifier = 0 == count( $args ) ? NULL : $args[0];
        if( 'list' == $sub_action )
        {
          return $this->get_record_list( $subject, $modifier, $inverted );
        }
        else if( 'count' == $sub_action )
        {
          return $this->get_record_count( $subject, $modifier, $inverted );
        }
      }
    }

    // if we get here then something went wrong
    throw $exception;
  }
  
  /**
   * Returns the record with foreign keys referencing the record table.
   * This method is used to select a record's parent record in many-to-one relationships.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $record_type The type of record.
   * @return record
   * @access protected
   */
  protected function get_record( $record_type )
  {
    // check the primary key value
    if( is_null( $this->column_values[ static::get_primary_key_name() ] ) )
    { 
      log::warning( 'Tried to query record with no id.' );
      return NULL;
    }
    
    $foreign_key_name = $record_type.'_id';

    // make sure this table has the correct foreign key
    if( !static::column_exists( $foreign_key_name ) )
    { 
      log::warning( 'Tried to get invalid record type: '.$record_type );
      return NULL;
    }

    // create the record using the foreign key
    $record = NULL;
    if( !is_null( $this->column_values[$foreign_key_name] ) )
    {
      $class_name = '\\sabretooth\\database\\'.$record_type;
      $record = new $class_name( $this->column_values[$foreign_key_name] );
    }

    return $record;
  }

  /**
   * Returns an array of records from the joining record table.
   * This method is used to select a record's child records in one-to-many or many-to-many
   * relationships.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $record_type The type of record.
   * @param modifier $modifier A modifier to apply to the count.
   * @param boolean $inverted Whether to invert the count (count records NOT in the joining table).
   * @param boolean $count If true then this method returns the count instead of list of records.
   * @return array( record ) | int
   * @access protected
   */
  protected function get_record_list(
    $record_type, $modifier = NULL, $inverted = false, $count = false )
  {
    $table_name = static::get_table_name();
    $primary_key_name = $table_name.'.'.static::get_primary_key_name();
    $foreign_class_name = '\\sabretooth\\database\\'.$record_type;

    // check the primary key value
    $primary_key_value = $this->column_values[ static::get_primary_key_name() ];
    if( is_null( $primary_key_value ) )
    { 
      log::warning( 'Tried to query record with no id.' );
      return $count ? 0 : array();
    }
      
    // this method varies depending on the relationship type
    $relationship = static::get_relationship( $record_type );
    if( relationship::NONE == $relationship )
    {
      log::err(
        sprintf( 'Tried to get a %s list from a %s, but there is no relationship between the two.',
                 $record_type,
                 $table_name() ) );
      return $count ? 0 : array();
    }
    else if( relationship::ONE_TO_ONE == $relationship )
    {
      log::err(
        sprintf( 'Tried to get a %s list from a %s, but there is a '.
                 'one-to-one relationship between the two.',
                 $record_type,
                 $table_name() ) );
      return $count ? 0 : array();
    }
    else if( relationship::ONE_TO_MANY == $relationship )
    {
      if( is_null( $modifier ) ) $modifier = new modifier();
      if( $inverted )
      {
        $modifier->where( $table_name.'_id', '=', NULL );
        $modifier->or_where( $table_name.'_id', '!=', $primary_key_value );
      }
      else $modifier->where( $table_name.'_id', '=', $primary_key_value );

      return $count
        ? $foreign_class_name::count( $modifier )
        : $foreign_class_name::select( $modifier );
    }
    else if( relationship::MANY_TO_MANY == $relationship )
    {
      $joining_table_name = static::get_joining_table_name( $record_type );
      $foreign_key_name = $record_type.'.'.$foreign_class_name::get_primary_key_name();
      $joining_primary_key_name = $joining_table_name.'.'.$table_name.'_id';
      $joining_foreign_key_name = $joining_table_name.'.'.$record_type.'_id';
      if( is_null( $modifier ) ) $modifier = new modifier();
  
      if( $inverted )
      { // we need to invert the list
        // first create SQL to match all records in the joining table
        $sub_modifier = new modifier();
        $sub_modifier->where( $foreign_key_name, '=', $joining_foreign_key_name, false );
        $sub_modifier->where( $joining_primary_key_name, '=', $primary_key_name, false );
        $sub_modifier->where( $primary_key_name, '=', $primary_key_value );
        $sub_select_sql =
          sprintf( 'SELECT %s FROM %s, %s, %s %s',
                   $joining_foreign_key_name,
                   $record_type,
                   $joining_table_name,
                   $table_name,
                   $sub_modifier->get_sql() );
  
        // now create SQL that gets all primary ids that are NOT in that list
        $modifier->where( $foreign_key_name, 'NOT IN', $sub_select_sql, false );
        $sql = sprintf( $count
                          ? 'SELECT COUNT( %s ) FROM %s %s'
                          : 'SELECT %s FROM %s %s',
                        $foreign_key_name,
                        $record_type,
                        $modifier->get_sql() );
      }
      else
      { // no inversion, just select the records from the joining table
        $modifier->where( $foreign_key_name, '=', $joining_foreign_key_name, false );
        $modifier->where( $joining_primary_key_name, '=', $primary_key_name, false );
        $modifier->where( $primary_key_name, '=', $primary_key_value );
        $sql = sprintf( $count
                          ? 'SELECT COUNT( %s ) FROM %s, %s, %s %s'
                          : 'SELECT %s FROM %s, %s, %s %s',
                        $joining_foreign_key_name,
                        $record_type,
                        $joining_table_name,
                        $table_name,
                        $modifier->get_sql() );
      }
      
      if( $count )
      {
        return intval( static::db()->get_one( $sql ) );
      }
      else
      {
        $ids = static::db()->get_col( $sql );
        $records = array();
        foreach( $ids as $id ) $records[] = new $foreign_class_name( $id );
        return $records;
      }
    }
    
    // if we get here then the relationship type is unknown
    log::crit(
      sprintf( 'Record %s has an unknown relationship to %s.',
               $table_name(),
               $record_type ) );
    return $count ? 0 : array();
  }

  /**
   * Returns the number of records in the joining record table.
   * This method is used to count a record's child records in one-to-many or many-to-many
   * relationships.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $record_type The type of record.
   * @param modifier $modifier A modifier to apply to the count.
   * @param boolean $inverted Whether to invert the count (count records NOT in the joining table).
   * @return int
   * @access protected
   */
  protected function get_record_count( $record_type, $modifier = NULL, $inverted = false )
  {
    return $this->get_record_list( $record_type, $modifier, $inverted, true );
  }

  /**
   * Given an array of ids, this method adds associations between the current and foreign record
   * by adding rows into the joining "has" table.
   * This method is used to add child records for many-to-many relationships.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $record_type The type of record.
   * @param int|array(int) $ids A single or array of primary key values for the record(s) being
   *                       added.
   * @access protected
   */
  protected function add_records( $record_type, $ids )
  {
    // warn if we are in read-only mode
    if( $this->read_only )
    {
      log::warning(
        'Tried to add '.$record_type.' records to read-only record.' );
      return;
    }
    
    // check the primary key value
    $primary_key_value = $this->column_values[ static::get_primary_key_name() ];
    if( is_null( $primary_key_value ) )
    { 
      log::warning( 'Tried to query record with no id.' );
      return;
    }

    // this method only supports many-to-many relationships.
    $relationship = static::get_relationship( $record_type );
    if( relationship::MANY_TO_MANY != $relationship )
    {
      log::err(
        sprintf( 'Tried to add %s to a %s without a many-to-many relationship between the two.',
                 util::prulalize( $record_type ),
                 static::get_table_name() ) );
      return;
    }
    
    $joining_table_name = static::get_joining_table_name( $record_type );
    
    // if ids is not an array then create a single-element array with it
    if( !is_array( $ids ) ) $ids = array( $ids );

    $values = '';
    $first = true;
    foreach( $ids as $foreign_key_value )
    {
      if( !$first ) $values .= ', ';
      $values .= sprintf( '(%s, %s)',
                       database::format_string( $primary_key_value ),
                       database::format_string( $foreign_key_value ) );
      $first = false;
    }

    static::db()->execute(
      sprintf( 'INSERT INTO %s (%s_id, %s_id) VALUES %s',
               $joining_table_name,
               static::get_table_name(),
               $record_type,
               $values ) );
  }

  /**
   * Given an id, this method removes the association between the current and record by removing
   * the corresponding row from the joining "has" table.
   * This method is used to remove child records from one-to-many or many-to-many relationships.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $record_type The type of record.
   * @param int $id The primary key value for the record being removed.
   * @access protected
   */
  protected function remove_record( $record_type, $id )
  {
    // warn if we are in read-only mode
    if( $this->read_only )
    {
      log::warning(
        'Tried to remove '.$foreign_table_name.' records to read-only record.' );
      return;
    }

    // check the primary key value
    $primary_key_value = $this->column_values[ static::get_primary_key_name() ];
    if( is_null( $primary_key_value ) )
    { 
      log::warning( 'Tried to query record with no id.' );
      return;
    }

    // this method varies depending on the relationship type
    $relationship = static::get_relationship( $record_type );
    if( relationship::NONE == $relationship )
    {
      log::err(
        sprintf( 'Tried to remove a %s from a %s, but there is no relationship between the two.',
                 $record_type,
                 static::get_table_name() ) );
    }
    else if( relationship::ONE_TO_ONE == $relationship )
    {
      log::err(
        sprintf( 'Tried to remove a %s from a %s, but there is a '.
                 'one-to-one relationship between the two.',
                 $record_type,
                 static::get_table_name() ) );
    }
    else if( relationship::ONE_TO_MANY == $relationship )
    {
      $foreign_class_name = '\\sabretooth\\database\\'.$record_type;
      $record = new $foreign_class_name( $id );
      $record->delete();
    }
    else if( relationship::MANY_TO_MANY == $relationship )
    {
      $joining_table_name = static::get_joining_table_name( $record_type );
  
      $modifier = new modifier();
      $modifier->where( static::get_table_name().'_id', '=', $primary_key_value );
      $modifier->where( $record_type.'_id', '=', $id );
  
      static::db()->execute(
        sprintf( 'DELETE FROM %s %s',
                 $joining_table_name,
                 $modifier->get_sql() ) );
    }
    else
    {
      // if we get here then the relationship type is unknown
      log::crit(
        sprintf( 'Record %s has an unknown relationship to %s.',
                 static::get_table_name(),
                 $record_type ) );
    }
  }
  
  /**
   * Gets the name of the joining table between this record and another.
   * If no such table exists then an empty string is returned.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $record_type The type of record.
   * @static
   * @access protected
   */
  protected static function get_joining_table_name( $record_type )
  {
    // the joining table may be <table>_has_<foreign_table> or <foreign>_has_<table>
    $table_name = static::get_table_name();
    $forward_joining_table_name = $table_name.'_has_'.$record_type;
    $reverse_joining_table_name = $record_type.'_has_'.$table_name;
    
    $joining_table_name = "";
    if( static::db()->table_exists( $forward_joining_table_name ) )
    {
      $joining_table_name = $forward_joining_table_name;
    }
    else if( static::db()->table_exists( $reverse_joining_table_name ) )
    {
      $joining_table_name = $reverse_joining_table_name;
    }
    
    return $joining_table_name;
  }
  
  /**
   * Gets the type of relationship this record has to another record.
   * See the relationship class for return values.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $record_type The type of record.
   * @return int (relationship::const)
   * @static
   * @access public
   */
  public static function get_relationship( $record_type )
  {
    $type = relationship::NONE;
    $class_name = '\\sabretooth\\database\\'.$record_type;
    if( $class_name::column_exists( static::get_table_name().'_id' ) )
    { // the record_type has a foreign key for this record
      $type = static::column_exists( $record_type.'_id' )
            ? relationship::ONE_TO_ONE
            : relationship::ONE_TO_MANY;
    }
    else if( 0 < strlen( static::get_joining_table_name( $record_type ) ) )
    { // a joining table was found
      $type = relationship::MANY_TO_MANY;
    }

    return $type;
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
   * @param boolean $count If true the total number of records instead of a list
   * @return array( record ) | int
   * @static
   * @access public
   */
  public static function select( $modifier = NULL, $count = false )
  {
    $this_table = static::get_table_name();
    
    // check to see if the modifier is sorting a value in a foreign table
    $table_list = array( $this_table );
    if( !is_null( $modifier ) )
    {
      // build an array of all foreign tables in the modifier
      $columns = $modifier->get_where_columns();
      $columns = array_merge( $columns, $modifier->get_order_columns() );
      $tables = array();
      foreach( $columns as $index => $column ) $tables[] = strstr( $column, '.', true );
      $tables = array_unique( $tables, SORT_STRING );

      foreach( $tables as $table )
      {
        if( $table && 0 < strlen( $table ) && $table != $this_table )
        {
          // check to see if we have a foreign key for this table
          $foreign_key_name = $table.'_id';
          if( static::column_exists( $foreign_key_name ) )
          {
            $class_name = '\\sabretooth\\database\\'.$table;
            // add the table to the list to select and join it in the modifier
            $table_list[] = $table;
            $modifier->where(
              $this_table.'.'.$foreign_key_name,
              '=',
              $table.'.'.$class_name::get_primary_key_name(), false );
          }
          // check to see if the foreign table has this table as a foreign key
          else if( static::db()->column_exists( $table, $this_table.'_id' ) )
          {
            // add the table to the list to select and join it in the modifier
            $table_list[] = $table;
            $modifier->where(
              $table.'.'.$this_table.'_id',
              '=',
              $this_table.'.'.static::get_primary_key_name(), false );
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

    $sql = sprintf( $count ? 'SELECT COUNT( %s.%s ) FROM %s %s' : 'SELECT %s.%s FROM %s %s',
                    $this_table,
                    static::get_primary_key_name(),
                    $select_tables,
                    is_null( $modifier ) ? '' : $modifier->get_sql() );

    if( $count )
    {
      return intval( static::db()->get_one( $sql ) );
    }
    else
    {
      $id_list = static::db()->get_col( $sql );
      $records = array();
      foreach( $id_list as $id ) $records[] = new static( $id );
      return $records;
    }
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
  public static function count( $modifier = NULL )
  {
    return static::select( $modifier, true );
  }

  /**
   * Get record using unique key.
   * 
   * This method returns an instance of the record using the name and value of a unique key.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $column a column with the unique key property
   * @param string $value the value of the column to match
   * @return database\record
   * @static
   * @access public
   */
  public static function get_unique_record( $column, $value )
  {
    $record = NULL;

    // make sure the column is unique
    if( static::db()->get_column_key( static::get_table_name(), $column ) )
    {
      // this returns null if no records are found
      $modifier = new modifier();
      $modifier->where( $column, '=', $value );

      $id = static::db()->get_one(
        sprintf( 'SELECT %s FROM %s %s',
                 static::get_primary_key_name(),
                 static::get_table_name(),
                 $modifier->get_sql() ) );

      if( !is_null( $id ) ) $record = new static( $id );
    }
    return $record;
  }

  /**
   * Returns the name of the table associated with this record.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return string
   * @access public
   */
  public static function get_table_name()
  {
    // Table and class names (without namespaces) should always be identical (with the exception
    // of the table prefix
    $prefix = static::db()->get_prefix();
    return $prefix.substr( strrchr( get_called_class(), '\\' ), 1 );
  }
  
  /**
   * Returns an array of column names for this table.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return array( string )
   * @access public
   */
  public function get_column_names()
  {
    return static::db()->get_column_names( static::get_table_name() );
  }

  /**
   * Returns the name of this record's primary key.
   * The schema does not currently support multiple-column primary keys, so this method always
   * returns a single column name.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return string
   * @access public
   */
  public static function get_primary_key_name()
  {
    return static::$primary_key_name;
  }
  
  /**
   * Returns an array of all enum values for a particular column.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $column_name A column name in the record's corresponding table.
   * @return array( string )
   * @access public
   */
  public static function get_enum_values( $column_name )
  {
    // match all strings in single quotes, then cut out the quotes from the match and return them
    $type = static::db()->get_column_type( static::get_table_name(), $column_name );
    preg_match_all( "/'[^']+'/", $type, $matches );
    $values = array();
    foreach( current( $matches ) as $match ) $values[] = substr( $match, 1, -1 );

    return $values;
  }
  
  /**
   * Convenience method for database::column_exists(), but for this record
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $column_name A column name.
   * @return string
   * @static
   * @access public
   */
  public static function column_exists( $column_name )
  {
    return static::db()->column_exists( static::get_table_name(), $column_name );
  }

  /**
   * Returns the record's database.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return database
   * @static
   * @access public
   */
  public static function db()
  {
    return bus\session::self()->get_database();
  }

  /**
   * Determines whether the record is read only (no modifying the database).
   * @var boolean
   * @access protected
   */
  protected $read_only = false;

  /**
   * The name of the table's primary key column.
   * @var string
   * @access protected
   */
  protected static $primary_key_name = 'id';

  /**
   * Holds all table column values in an associate array where key=>value is
   * column_name=>column_value
   * @var array
   * @access private
   */
  private $column_values = array();

  /**
   * Determines whether or not to write changes to the database on deletion.
   * This value affects ALL records.
   * @var boolean
   * @static
   * @access public
   */
  public static $auto_save = false;
}
?>
