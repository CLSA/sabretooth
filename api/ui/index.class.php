<?php
/**
 * index.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 */

namespace sabretooth\ui;

/**
 * index widget
 * 
 * @package sabretooth\ui
 */
class index extends widget
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
    // define all template variables for this widget
    $this->add_variable_names(
      array( 'survey_active' ) );
    
    // create and setup the settings widget
    $db_user = \sabretooth\session::singleton()->get_user();
    $db_site = \sabretooth\session::singleton()->get_site();
    $db_role = \sabretooth\session::singleton()->get_role();
    
    $sites = array();
    $db_site_array = $db_user->get_sites();
    foreach( $db_site_array as $db_site )
    {
      array_push( $sites, $db_site->name );
    }

    $roles = array();
    $db_role_array = $db_user->get_roles( $db_site );
    foreach( $db_role_array as $db_role )
    {
      array_push( $roles, $db_role->name );
    }

    $w_settings = new settings();
    $w_settings->set_variable( 'user_name', $db_user->name );
    $w_settings->set_variable( 'current_site_name', $db_site->name );
    $w_settings->set_variable( 'current_role_name', $db_role->name );
    $w_settings->set_variable( 'roles', $roles );
    $w_settings->set_variable( 'sites', $sites );
  }
}
?>
