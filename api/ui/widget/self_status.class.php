<?php
/**
 * self_status.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui\widget;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * widget self status
 */
class self_status extends \cenozo\ui\widget\self_status
{
  /**
   * Finish setting the variables in a widget.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access protected
   */
  protected function setup()
  {
    parent::setup();
  }

  /**
   * Processes arguments, preparing them for the operation.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @throws exception\notice
   * @access protected
   */
  protected function prepare()
  {
    parent::prepare();

    $voip_manager = lib::create( 'business\voip_manager' );
    $this->set_variable( 'sip_enabled', $voip_manager->get_sip_enabled() );
    $this->set_variable( 'on_call', !is_null( $voip_manager->get_call() ) );
    $this->set_variable(
      'home', 'operator' != lib::create( 'business\session' )->get_role()->name );
  }
}
?>
