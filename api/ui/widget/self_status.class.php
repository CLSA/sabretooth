<?php
/**
 * self_status.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui\widget;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * widget self status
 * 
 * @package sabretooth\ui
 */
class self_status extends \cenozo\ui\widget\self_status
{
  /**
   * Finish setting the variables in a widget.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function finish()
  {
    parent::finish();

    $voip_manager = lib::create( 'business\voip_manager' );
    $this->set_variable( 'sip_enabled', $voip_manager->get_sip_enabled() );
    $this->set_variable( 'on_call', !is_null( $voip_manager->get_call() ) );
  }
}
?>
