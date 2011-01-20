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
    // define all template variables for this widget
    $this->add_variable_names(
      array( 'user_name',
             'current_site_name',
             'current_role_name',
             'sites',
             'roles' ) );
  }
}
?>
