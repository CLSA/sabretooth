<?php
/**
 * limesurvey.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\database
 * @filesource
 */

namespace sabretooth\database\limesurvey;

/**
 * This is the abstract database table object for all limesurvey tables.
 * 
 * @package sabretooth\database
 */
abstract class active_record extends \sabretooth\database\active_record
{
  /**
   * Magic call method.
   * 
   * Disables the parent method so that it is compatible with limesurvey tables.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @throws exception\runtime
   * @access public
   */
  public function __call( $name, $args )
  {
    throw new \sabretooth\exception\runtime(
      sprintf( 'Call to undefined function: %s::%s().',
               get_called_class(),
               $name ), __METHOD__ );
  }

  /**
   * Select a number of records.
   * 
   * Overrides the parent method so that it is compatible with limesurvey tables.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the selection.
   * @return array( active_record )
   * @static
   * @access public
   */
  public static function select( $modifier = NULL )
  {
    $records = array();
    $id_list = self::get_col(
      sprintf( 'SELECT %s FROM %s %s',
               static::$primary_key_name,
               static::get_table_name(),
               is_null( $modifier ) ? '' : $modifier->get_sql() ) );

    foreach( $id_list as $id ) array_push( $records, new static( $id ) );

    return $records;
  }

  /**
   * Get record using unique key.
   * 
   * Overrides the parent method so that it is compatible with limesurvey tables.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $column a column with the unique key property
   * @param string $value the value of the column to match
   * @return database\active_record
   * @static
   * @access public
   */
  public static function get_unique_record( $column, $value )
  {
    // same as parent but with a different database and table name prefix
    $record = NULL;
    $database = \sabretooth\session::self()->get_setting( 'survey_db', 'database' );

    // determine the unique key(s)
    $modifier = new modifier();
    $modifier->where( 'TABLE_SCHEMA', $database );
    $modifier->where( 'TABLE_NAME', self::get_table_name() );
    $modifier->where( 'COLUMN_KEY', 'UNI' );

    $unique_keys = self::get_col(
      sprintf( 'SELECT COLUMN_NAME FROM information_schema.COLUMNS %s',
               $modifier->get_sql() ) );

    // make sure the column is unique
    if( in_array( $column, $unique_keys ) )
    {
      // this returns null if no records are found
      $modifier = new modifier();
      $modifier->where( $column, $value );

      $id = self::get_one(
        sprintf( 'SELECT %s FROM %s %s',
                 static::$primary_id_name,
                 self::get_table_name(),
                 $modifier->get_sql() ) );

      if( !is_null( $id ) ) $record = new static( $id );
    }
    return $record;
  }

  /**
   * Returns the name of the table associated with this active record.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return string
   * @access protected
   */
  protected static function get_table_name()
  {
    // Table and class names (without namespaces) should always be identical, but we need to have
    // their database name and prefix added to them.
    $database = \sabretooth\session::self()->get_setting( 'survey_db', 'database' );
    $prefix = \sabretooth\session::self()->get_setting( 'survey_db', 'prefix' );
    return $database.'.'.$prefix.parent::get_table_name();
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
    // same as parent method but with a different database and table name prefix
    $database = \sabretooth\session::self()->get_setting( 'survey_db', 'database' );
    $prefix = \sabretooth\session::self()->get_setting( 'survey_db', 'prefix' );
    $modifier = new modifier();
    $modifier->where( 'TABLE_SCHEMA', $database );
    $modifier->where( 'Table_Name', $prefix.$name );

    $count = self::get_one(
      sprintf( 'SELECT COUNT(*) FROM information_schema.TABLES %s',
               $modifier->get_sql() ) );

    return 0 < $count;
  }
}
?>
