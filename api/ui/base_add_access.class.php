<?php
/**
 * base_add_access.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui;

/**
 * Base class for adding access to sites and users.
 * 
 * @package sabretooth\ui
 */
class base_add_access extends base_add_list
{
  /**
   * Constructor
   * 
   * Defines all variables which need to be set for the associated template.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $subject The operation's subject.
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $subject, $args )
  {
    parent::__construct( $subject, 'access', $args );
    
    // build the role list widget
    $this->role_list = new role_list( $args );
    $this->role_list->set_parent( $this, 'edit' );
    $this->role_list->set_heading( 'Select roles to grant to the selected users' );
  }

  public function finish()
  {
    parent::finish();

    $this->role_list->finish();
    $this->set_variable( 'role_list', $this->role_list->get_variables() );
  }

  /**
   * The role list widget used to define the access type.
   * @var role_list
   * @access protected
   */
  protected $list_widget = NULL;
}
?>
