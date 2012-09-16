<?php
/**
 * appointment_new.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui\push;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * push: appointment new
 *
 * Create a new appointment.
 */
class appointment_new extends \cenozo\ui\push\base_new
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
   * Validate the operation.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @throws exception\notice
   * @access protected
   */
  protected function validate()
  {
    parent::validate();

    $columns = $this->get_argument( 'columns' );

    // make sure the datetime column isn't blank
    if( !array_key_exists( 'datetime', $columns ) || 0 == strlen( $columns['datetime'] ) )
      throw lib::create( 'exception\notice', 'The date/time cannot be left blank.', __METHOD__ );
    
    if( !$this->get_argument( 'force', false ) )
    {
      // validate the appointment time
      $this->get_record()->participant_id = $columns['participant_id'];
      $this->get_record()->datetime = $columns['datetime'];
      $this->get_record()->type = $columns['type'];
      if( !$this->get_record()->validate_date() )
      {
        $db_participant = lib::create( 'database\participant', $this->get_record()->participant_id );
        $db_site = $db_participant->get_primary_site();

        // determine the full and half appointment intervals
        $setting_manager = lib::create( 'business\setting_manager' );
        $half_duration = $setting_manager->get_setting( 'appointment', 'half duration', $db_site );
        $full_duration = $setting_manager->get_setting( 'appointment', 'full duration', $db_site );
        $duration = 'full' == $this->get_record()->type ? $full_duration : $half_duration;

        $start_datetime_obj = util::get_datetime_object( $this->get_record()->datetime );
        $end_datetime_obj = clone $start_datetime_obj;
        $end_datetime_obj->add( new \DateInterval( sprintf( 'PT%dM', $duration ) ) );
        throw lib::create( 'exception\notice',
          sprintf(
            'Unable to create a %s appointment (%d minutes) since there is not '.
            'at least 1 slot available from %s and %s.',
            $this->get_record()->type,
            $duration,
            $start_datetime_obj->format( 'H:i' ),
            $end_datetime_obj->format( 'H:i' ) ),
          __METHOD__ );
      }
    }
  }
}
?>
