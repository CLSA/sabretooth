<?php
/**
 * appointment_feed.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui\pull;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * pull: appointment feed
 * 
 * @package sabretooth\ui
 */
class appointment_feed extends \cenozo\ui\pull\base_feed
{
  /**
   * Constructor
   * 
   * Defines all variables required by the appointment feed.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args Pull arguments.
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'appointment', $args );
  }
  
  /**
   * Returns the data provided by this feed.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return array
   * @access public
   */
  public function finish()
  {
    // create a list of appointments between the feed's start and end time
    $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'datetime', '>=', $this->start_datetime );
    $modifier->where( 'datetime', '<', $this->end_datetime );

    $event_list = array();
    $db_site = lib::create( 'business\session' )->get_site();
    $appointment_class_name = lib::get_class_name( 'database\appointment' );
    $setting_manager = lib::create( 'business\setting_manager');
    foreach( $appointment_class_name::select_for_site( $db_site, $modifier ) as $db_appointment )
    {
      $start_datetime_obj = util::get_datetime_object( $db_appointment->datetime );
      $end_datetime_obj = clone $start_datetime_obj;
      $duration = $db_appointment->type == 'full' ? 
                  $setting_manager->get_setting( 'appointment', 'full duration' ) : 
                  $setting_manager->get_setting( 'appointment', 'half duration' );
      $end_datetime_obj->modify(  sprintf( '+%d minute', $duration ) );

      $db_participant = $db_appointment->get_participant();
      $event_list[] = array(
        'id' => $db_appointment->id,
        'title' => is_null( $db_participant->uid ) || 0 == strlen( $db_participant->uid ) ?
          $db_participant->first_name.' '.$db_participant->last_name :
          $db_participant->uid,
        'allDay' => false,
        'start' => $start_datetime_obj->format( \DateTime::ISO8601 ),
        'end' => $end_datetime_obj->format( \DateTime::ISO8601 ) );
    }

    return $event_list;
  }
}
?>
