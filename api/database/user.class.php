<?php
/**
 * user.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\database
 * @filesource
 */

namespace sabretooth\database;

/**
 * user: active record
 *
 * @package sabretooth\database
 */
class user extends base_access
{
  /**
   * Adds a list of sites to the user with the given role.
   * 
   * @author Patrick Emond <emondpd@mcamster.ca>
   * @param int $site_id_list The sites to add.
   * @param int $role_id The role to add them under.
   * @throws exeception\argument
   * @access public
   */
  public function add_access( $site_id_list, $role_id )
  {
    // make sure the site id list argument is a non-empty array of ids
    if( !is_array( $site_id_list ) || 0 == count( $site_id_list ) )
      throw new \sabretooth\exception\argument( 'site_id_list', $site_id_list, __METHOD__ );

    // make sure the role id argument is valid
    if( 0 >= $role_id )
      throw new \sabretooth\exception\argument( 'role_id', $role_id, __METHOD__ );

    $values = '';
    $first = true;
    foreach( $site_id_list as $id )
    {
      if( !$first ) $values .= ', ';
      $values .= sprintf( '(%s, %s, %s)',
                       active_record::format_string( $id ),
                       active_record::format_string( $role_id ),
                       active_record::format_string( $this->id ) );
      $first = false;
    }

    self::execute(
      sprintf( 'INSERT IGNORE INTO access (site_id, role_id, user_id) VALUES %s',
               $values ) );
  }

  /**
   * Removes a list of sites to the user who have the given role.
   * 
   * @author Patrick Emond <emondpd@mcamster.ca>
   * @param int $access_id The access record to remove.
   * @access public
   */
  public function remove_access( $access_id )
  {
    $modifier = new modifier();
    $modifier->where( 'id', '=', $access_id );
    // this just to make sure the access belongs to this user
    $modifier->where( 'user_id', '=', $this->id );

    self::execute(
      sprintf( 'DELETE FROM access %s',
               $modifier->get_sql() ) );
  }
}
?>
