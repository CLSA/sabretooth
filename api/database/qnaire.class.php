<?php
/**
 * qnaire.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\database;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * qnaire: record
 */
class qnaire extends \cenozo\database\has_rank
{
  /**
   * Allow access to default interview method object
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return database\interview_method
   * @access public
   */
  public function get_default_interview_method()
  {
    return is_null( $this->default_interview_method_id ) ?
      NULL : lib::create( 'database\interview_method', $this->default_interview_method_id );
  }

  /**
   * Returns whether a particular interview method is in use by any qnaire
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\interview_method $db_interview_method
   * @return boolean
   * @access public
   * @static
   */
  public static function is_interview_method_in_use( $db_interview_method )
  {
    $database_class_name = lib::get_class_name( 'database\database' );

    // quick custom sql to determine whether any qnaire is using a particular interview method
    return (bool) static::db()->get_one( sprintf(
      'SELECT 0 < COUNT(*) '.
      'FROM qnaire_has_interview_method '.
      'WHERE interview_method_id = %s',
      $database_class_name::format_string( $db_interview_method->id ) ) );
  }
}
