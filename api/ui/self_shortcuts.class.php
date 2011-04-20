<?php
/**
 * self_shortcuts.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui;
use sabretooth\log, sabretooth\util;
use sabretooth\business as bus;
use sabretooth\database as db;
use sabretooth\exception as exc;

/**
 * widget self shortcuts
 * 
 * @package sabretooth\ui
 */
class self_shortcuts extends widget
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
    parent::__construct( 'self', 'shortcuts', $args );
  }

  /**
   * Finish setting the variables in a widget.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function finish()
  {
    parent::finish();
    
    $db_user = bus\session::self()->get_user();
    $db_role = bus\session::self()->get_role();

    $this->set_variable( 'dialpad',
      0 < count( bus\voip_manager::self()->get_calls( $db_user->name ) ) );
    $this->set_variable( 'calculator', true );
    $this->set_variable( 'navigation', 'operator' != $db_role->name );
    $this->set_variable( 'refresh', true );
    $this->set_variable( 'home', true );
  }
}
?>
