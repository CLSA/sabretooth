<?php
/**
 * operation.class.php
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
 * operation: record
 *
 * @package sabretooth\database
 */
class operation extends record
{
  /**
   * Get an operation given it's type, subject and name.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $type "action" or "widget"
   * @param string $subject
   * @param string $name
   * @static
   * @access public
   */
  public static function get_operation( $type, $subject, $name )
  {
    $modifier = new modifier();
    $modifier->where( 'type', '=', $type );
    $modifier->where( 'subject', '=', $subject );
    $modifier->where( 'name', '=', $name );

    $id = static::db()->get_one(
      sprintf( 'SELECT id FROM %s %s',
               static::get_table_name(),
               $modifier->get_sql() ) );

    return is_null( $id ) ? NULL : new static( $id );
  }
}
?>
