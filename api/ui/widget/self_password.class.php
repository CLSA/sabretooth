<?php
/**
 * self_password.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui\widget;
use sabretooth\log, sabretooth\util;
use sabretooth\business as bus;
use sabretooth\database as db;
use sabretooth\exception as exc;

/**
 * widget self password
 * 
 * @package sabretooth\ui
 */
class self_password extends \sabretooth\ui\widget
{
  /**
   * Constructor
   * 
   * Defines all variables which need to be set for the associated template.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'self', 'password', $args );
    $this->show_heading( false );
  }

  /**
   * Finish setting the variables in a widget.
   * 
   * Defines all variables which need to be set for the associated template.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function finish()
  {
    parent::finish();
    
    // if the current password is "password" then mark the widget as the first password change
    $ldap_manager = bus\ldap_manager::self();
    $this->set_variable( 'first_password',
      $ldap_manager->validate_user( bus\session::self()->get_user()->name, 'password' ) );
  }
}
?>
