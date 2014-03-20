<?php
/**
 * appointment_edit.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui\push;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * push: appointment edit
 *
 * Edit a appointment.
 */
class appointment_edit extends \cenozo\ui\push\base_edit
{
  /**
   * Constructor.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args Push arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'appointment', $args );
  }
  
  /**
   * This method executes the operation's purpose.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access protected
   */
  protected function execute()
  {
    parent::execute();

    // send message to IVR
    $record = $this->get_record();
    $ivr_manager = lib::create( 'business\ivr_manager' );
    $ivr_manager->set_appointment(
      $record->get_participant(),
      $record->get_phone(),
      $record->datetime );

    $this->get_record()->get_participant()->update_queue_status();
  }
}
