<?php
/**
 * settings.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 */

namespace sabretooth\ui;

/**
 * settings widget
 * 
 * @package sabretooth\ui
 */
class settings extends widget
{
  /**
   * Constructor
   * 
   * Defines all variables which need to be set for the associated template.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function __construct()
  {
    parent::__construct();

    // create and setup the settings widget
    $db_user = \sabretooth\session::self()->get_user();
    $db_site = \sabretooth\session::self()->get_site();
    $db_role = \sabretooth\session::self()->get_role();
    
    $site_names = array();
    $sites = $db_user->get_sites();
    foreach( $sites as $site )
    {
      array_push( $site_names, $site->name );
    }

    $role_names = array();
    $roles = $db_user->get_roles( $db_site );
    foreach( $roles as $role )
    {
      array_push( $role_names, $role->name );
    }

    $this->set_variable( 'user_name', $db_user->name );
    $this->set_variable( 'current_site_name', $db_site->name );
    $this->set_variable( 'current_role_name', $db_role->name );
    $this->set_variable( 'roles', $role_names );
    $this->set_variable( 'sites', $site_names );
  }
}
?>
