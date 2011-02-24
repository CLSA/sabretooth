<?php
/**
 * self_home.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui;

/**
 * widget self home
 * 
 * @package sabretooth\ui
 */
class self_home extends widget
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
    parent::__construct( 'self', 'home', $args );
    $this->set_heading( 'Home' );
    
    $session = \sabretooth\session::self();

    // determine the user's last activity
    $db_activity = $session->get_user()->get_last_activity();

    $this->set_variable( 'user_name', $session->get_user()->name );
    $this->set_variable( 'role_name', $session->get_role()->name );
    $this->set_variable( 'site_name', $session->get_site()->name );
    $this->set_variable( 'last_day', \sabretooth\util::get_date( $db_activity->date ) );
    $this->set_variable( 'last_time', \sabretooth\util::get_time( $db_activity->date ) );
  }
}
?>
