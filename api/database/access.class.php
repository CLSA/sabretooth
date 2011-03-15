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
 * access: record
 *
 * @package sabretooth\database
 */
class access extends record
{
  /**
   * Returns whether or not the access exists.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param user $db_user
   * @param site $db_site
   * @param role $db_role
   * @return boolean
   * @static
   * @access public
   */
  public static function exists( $db_user, $db_site, $db_role )
  {
    // validate arguments
    if( !is_object( $db_user ) || !is_a( $db_user, '\\sabretooth\\database\\user' ) )
    {
      throw new \sabretooth\exception\argument( 'user', $db_user, __METHOD__ );
    }
    else if( !is_object( $db_role ) || !is_a( $db_role, '\\sabretooth\\database\\role' ) )
    {
      throw new \sabretooth\exception\argument( 'role', $db_role, __METHOD__ );
    }
    else if( !is_object( $db_site ) || !is_a( $db_site, '\\sabretooth\\database\\site' ) )
    {
      throw new \sabretooth\exception\argument( 'site', $db_site, __METHOD__ );
    }

    $modifier = new modifier();
    $modifier->where( 'user_id', '=', $db_user->id );
    $modifier->where( 'role_id', '=', $db_role->id );
    $modifier->where( 'site_id', '=', $db_site->id );

    $id = static::db()->get_one(
      sprintf( 'SELECT id FROM access %s',
               $modifier->get_sql() ) );

    return !is_null( $id );
  }
}
?>
