<?php
/**
 * user_add_site.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui;

/**
 * widget user add_site
 * 
 * @package sabretooth\ui
 */
class user_add_site extends base_add_list
{
  /**
   * Constructor
   * 
   * Defines all variables which need to be set for the associated template.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $name The name of the operation.
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'user', 'site', $args );

    // build the role list widget
    $this->role_list = new role_list( $args );
    $this->role_list->set_parent( $this, 'edit' );
    $this->role_list->set_heading( 'Select which roles to grant access to at the selected sites' );

    // wording for the site list should be slightly different
    $this->list_widget->set_heading( 'Choose which sites to add the user to.' );
  }

  public function finish()
  {
    parent::finish();

    $this->role_list->finish();
    $this->set_variable( 'role_list', $this->role_list->get_variables() );
  }
}
?>
