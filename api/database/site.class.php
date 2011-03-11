<?php
/**
 * site.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\database
 * @filesource
 */

namespace sabretooth\database;

/**
 * site: record
 *
 * @package sabretooth\database
 */
class site extends base_access
{
  /**
   * Adds a list of users to the site with the given role.
   * 
   * @author Patrick Emond <emondpd@mcamster.ca>
   * @param int $user_id_list The users to add.
   * @param int $role_id The role to add them under.
   * @throws exeception\argument
   * @access public
   */
  public function add_access( $user_id_list, $role_id )
  {
    if( is_null( $this->id ) )
    {
      \sabretooth\log::warning( 'Tried to add access to site with no id.' );
      return;
    }

    // make sure the user id list argument is a non-empty array of ids
    if( !is_array( $user_id_list ) || 0 == count( $user_id_list ) )
      throw new \sabretooth\exception\argument( 'user_id_list', $user_id_list, __METHOD__ );
    
    // make sure the role id argument is valid
    if( 0 >= $role_id )
      throw new \sabretooth\exception\argument( 'role_id', $role_id, __METHOD__ );

    $values = '';
    $first = true;
    foreach( $user_id_list as $id )
    {
      if( !$first ) $values .= ', ';
      $values .= sprintf( '(%s, %s, %s)',
                       database::format_string( $id ),
                       database::format_string( $role_id ),
                       database::format_string( $this->id ) );
      $first = false;
    }

    static::db()->execute(
      sprintf( 'INSERT IGNORE INTO access (user_id, role_id, site_id) VALUES %s',
               $values ) );
  }

  /**
   * Removes a list of users to the site who have the given role.
   * 
   * @author Patrick Emond <emondpd@mcamster.ca>
   * @param int $access_id The access record to remove.
   * @access public
   */
  public function remove_access( $access_id )
  {
    if( is_null( $this->id ) )
    {
      \sabretooth\log::warning( 'Tried to remove access from site with no id.' );
      return;
    }

    $modifier = new modifier();
    $modifier->where( 'id', '=', $access_id );
    // this just to make sure the access belongs to this site
    $modifier->where( 'site_id', '=', $this->id );

    static::db()->execute(
      sprintf( 'DELETE FROM access %s',
               $modifier->get_sql() ) );
  }
}
?>
