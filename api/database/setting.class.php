<?php
/**
 * setting.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\database
 * @filesource
 */

namespace sabretooth\database;

/**
 * setting: record
 *
 * @package sabretooth\database
 */
class setting extends record
{
  /**
   * Get an setting given it's category and name
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $category
   * @param string $name
   * @static
   * @access public
   */
  public static function get_setting( $category, $name )
  {
    $modifier = new modifier();
    $modifier->where( 'category', '=', $category );
    $modifier->where( 'name', '=', $name );

    $id = static::db()->get_one(
      sprintf( 'SELECT id FROM %s %s',
               static::get_table_name(),
               $modifier->get_sql() ) );

    return is_null( $id ) ? NULL : new static( $id );
  }
}
?>
