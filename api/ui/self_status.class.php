<?php
/**
 * self_status.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui;

/**
 * widget self status
 * 
 * @package sabretooth\ui
 */
class self_status extends widget
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
    parent::__construct( 'self', 'status', $args );
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

    $this->set_variable( 'sip_enabled',
      \sabretooth\business\voip_manager::self()->get_sip_enabled() );
    $this->set_variable( 'on_call',
      0 < count( \sabretooth\business\voip_manager::self()->get_calls(
                   \sabretooth\business\session::self()->get_user()->name ) ) );
  }
}
?>
