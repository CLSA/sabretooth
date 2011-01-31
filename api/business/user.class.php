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
   * Set the current user's active site
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function set_site( $site_name )
  {
    $db_site = \sabretooth\database\site::get_unique_record( 'name', $site_name );
    if( NULL == $db_site )
      \sabretooth\log::error( "Invalid site name '$site_name'" );

    // get the first role associated with the site
    $session = \sabretooth\session::self();
    $db_role_array = $session->get_user()->get_roles( $db_site );
    if( 0 == count( $db_role_array ) )
      \sabretooth\log::error( "User has no access to site name '$site_name'" );

    $session::self()->set_site_and_role( $db_site, $db_role_array[0] );
  }

  /**
   * Set the current user's active role
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function set_role( $role_name )
  {
    $db_role = \sabretooth\database\role::get_unique_record( 'name', $role_name );
    if( NULL == $db_role )
      \sabretooth\log::error( "Invalid role name '$role_name'" );

    // get the first role associated with the role
    $session = \sabretooth\session::self();
    $db_site = $session->get_site();
    $db_role_array = $session->get_user()->get_roles( $db_site );
    if( !in_array( $db_role, $db_role_array ) )
      \sabretooth\log::error( "User has no access to role name '$role_name'" );

    $session::self()->set_site_and_role( $db_site, $db_role );
  }
  
  /**
   * Set the current user's theme
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function set_theme( $theme_name )
  {
    $session = \sabretooth\session::self();
    $session->get_user()->theme = $theme_name;
    $session->get_user()->save();
  }
  
  /**
   * List system users.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @throws exception\runtime
   * @access public
   */
  public function llist()
  {
    if( !$this->has_access( __FUNCTION__ ) )
      throw new \sabretooth\exception\permission( $this->get_db_operation( __FUNCTION__ ) );

    // nothing else required by this action
  }
}
?>
