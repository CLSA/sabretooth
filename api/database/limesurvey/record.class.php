<?php
/**
 * limesurvey.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\database
 * @filesource
 */

namespace sabretooth\database\limesurvey;
use sabretooth\log, sabretooth\util;
use sabretooth\business as bus;
use sabretooth\database as db;
use sabretooth\exception as exc;


/**
 * This is the abstract database table object for all limesurvey tables.
 * 
 * @package sabretooth\database
 */
abstract class record extends db\record
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
    throw new exc\runtime(
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
   * @param boolean $count If true the total number of records instead of a list
   * @return array( record )
   * @static
   * @access public
   */
  public static function select( $modifier = NULL, $count = false )
  {
    $sql = sprintf( $count ? 'SELECT COUNT( %s ) FROM %s %s' : 'SELECT %s FROM %s %s',
                    static::get_primary_key_name(),
                    static::get_table_name(),
                    is_null( $modifier ) ? '' : $modifier->get_sql() );
    
    if( $count )
    {
      return static::db()->get_one( $sql );
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
   * Returns the record's database.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return database
   * @static
   * @access protected
   */
  public static function db()
  {
    return bus\session::self()->get_survey_database();
  }

  /**
   * Returns the token name for a particular interview, phase and assignment
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\interview $db_interview 
   * @param database\interview $db_phase 
   * @param database\interview $db_assignment (only used if the phase is repeated)
   * @static
   * @access public
   */
  public static function get_token( $db_interview, $db_phase, $db_assignment = NULL )
  {
    return sprintf( '%s_%s_%s',
                    $db_interview->id,
                    $db_phase->id,
                    // repeated phases have the assignment id as the last part of the token
                    $db_phase->repeated && !is_null( $db_assignment ) ? $db_assignment->id : 0 );
  }
}
?>
