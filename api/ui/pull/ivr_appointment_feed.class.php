<?php
/**
 * ivr_appointment_feed.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui\pull;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * pull: ivr_appointment feed
 */
class ivr_appointment_feed extends \cenozo\ui\pull\base_feed
{
  /**
   * Constructor
   * 
   * Defines all variables required by the ivr_appointment feed.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args Pull arguments.
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'ivr_appointment', $args );
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

    // create a list of IVR appointments between the feed's start and end time
    $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'datetime', '>=', $this->start_datetime );
    $modifier->where( 'datetime', '<', $this->end_datetime );

    $this->data = array();
    $ivr_appointment_class_name = lib::get_class_name( 'database\ivr_appointment' );
    $setting_manager = lib::create( 'business\setting_manager');
    foreach( $ivr_appointment_class_name::select( $modifier ) as $db_ivr_appointment )
    {
      $start_datetime_obj = util::get_datetime_object( $db_ivr_appointment->datetime );
      $end_datetime_obj = clone $start_datetime_obj;
      $duration = $setting_manager->get_setting( 'appointment', 'full duration' );
      $end_datetime_obj->modify(  sprintf( '+%d minute', $duration ) );

      $db_participant = $db_ivr_appointment->get_interview()->get_participant();
      $this->data[] = array(
        'id' => $db_ivr_appointment->id,
        'title' => is_null( $db_participant->uid ) || 0 == strlen( $db_participant->uid ) ?
          $db_participant->first_name.' '.$db_participant->last_name :
          $db_participant->uid,
        'allDay' => false,
        'start' => $start_datetime_obj->format( \DateTime::ISO8601 ),
        'end' => $end_datetime_obj->format( \DateTime::ISO8601 ) );
    }
  }
}
