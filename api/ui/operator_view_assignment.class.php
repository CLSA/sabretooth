<?php
/**
 * operator_view_assignment.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui;

/**
 * widget operator view_assignment
 * 
 * @package sabretooth\ui
 */
class operator_view_assignment extends widget
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
    parent::__construct( 'operator', 'view_assignment', $args );
    $this->set_heading( 'Current Assignment' );
    $session = \sabretooth\session::self();
  }
}
?>
