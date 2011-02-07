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
    $id = self::get_one(
      'SELECT id '.
      'FROM '.static::get_table_name().' '.
      'WHERE type = "'.$type.'" '.
      'AND subject = "'.$subject.'" '.
      'AND name = "'.$name.'"' );

    return is_null( $id ) ? NULL : new static( $id );
  }
}
?>
