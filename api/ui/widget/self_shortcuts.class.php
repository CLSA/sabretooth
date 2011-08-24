<?php
/**
 * self_shortcuts.class.php
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
 * widget self shortcuts
 * 
 * @package sabretooth\ui
 */
class self_shortcuts extends \sabretooth\ui\widget
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
    
    $is_operator = 'operator' == bus\session::self()->get_role()->name;
    $this->set_variable( 'dialpad', !is_null( bus\voip_manager::self()->get_call() ) );
    $this->set_variable( 'timer',
      $is_operator && !is_null( bus\session::self()->get_current_phone_call() ) );
    $this->set_variable( 'calculator', true );
    $this->set_variable( 'navigation', !$is_operator );
    $this->set_variable( 'refresh', true );
    $this->set_variable( 'home', false );
  }
}
?>
