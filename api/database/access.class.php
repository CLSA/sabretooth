<?php
/**
 * access.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\database
 * @filesource
 */

namespace sabretooth\database;

/**
 * access: active record
 *
 * @package sabretooth\database
 */
class access extends active_record
{
  /**
   * Returns whether or not the access exists.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return boolean
   * @static
   * @access public
   */
  public static function exists( $user, $role, $site )
  {
    $modifier = new modifier();
    $modifier->where( 'user_id', '=', $user->id );
    $modifier->where( 'role_id', '=', $role->id );
    $modifier->where( 'site_id', '=', $site->id );

    $id = static::db()->get_one(
      sprintf( 'SELECT id FROM access %s',
               $modifier->get_sql() ) );

    return !is_null( $id );
  }
}
?>
