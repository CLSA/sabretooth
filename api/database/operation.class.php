<?php
/**
 * operation.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\database
 * @filesource
 */

namespace sabretooth\database;

/**
 * operation: active record
 *
 * @package sabretooth\database
 */
class operation extends active_record
{
  /**
   * Get an operation given it's type, subject and name.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @static
   * @access public
   */
  public static function get_operation( $type, $subject, $name )
  {
    $modifier = new modifier();
    $modifier->where( 'type', $type );
    $modifier->where( 'subject', $subject );
    $modifier->where( 'name', $name );

    $id = self::get_one(
      sprintf( 'SELECT id FROM %s %s',
               static::get_table_name(),
               $modifier->get_sql() ) );

    return is_null( $id ) ? NULL : new static( $id );
  }
}
?>
