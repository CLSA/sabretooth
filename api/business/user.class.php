<?php
/**
 * user.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\business
 */

namespace sabretooth\business;

/**
 * user: active record
 *
 * @package sabretooth\business
 */
class user extends operation
{
  /**
   * set_site: Set the current user's active site
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @throws exception\runtime
   * @access public
   */
  public function set_site( $site_name )
  {
    $db_site = \sabretooth\database\site::get_unique_record( 'name', $site_name );
    if( NULL == $db_site )
      \sabretooth\log::self()->error( "Invalid site name '$site_name'" );

    // get the first role associated with the site
    $session = \sabretooth\session::self();
    $db_role_array = $session->get_user()->get_roles( $db_site );
    if( 0 == count( $db_role_array ) )
      \sabretooth\log::self()->error( "User has no access to site name '$site_name'" );

    \sabretooth\session::self()->set_site_and_role( $db_site, $db_role_array[0] );
  }

  public function llist()
  {
    
  }
}
?>
