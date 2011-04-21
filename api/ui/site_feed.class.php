<?php
/**
 * site_feed.class.php
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
 * datum site feed
 * 
 * @package sabretooth\ui
 */
class site_feed extends base_feed
{
  /**
   * Constructor
   * 
   * Defines all variables required by the site feed.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args An associative array of arguments to be processed by the datum
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'site', $args );
  }
  
  /**
   * Returns the data provided by this feed.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return array
   * @access public
   */
  public function get_data()
  {
    $db_site = bus\session::self()->get_site();

    // start by creating an array with one element per day in the time span
    $start_datetime = new \DateTime( $this->start_date );
    $end_datetime = new \DateTime( $this->end_date );
    
    $days = array();
    $current_datetime = $start_datetime;
    while( $current_datetime->diff( $end_datetime )->days )
    {
      $days[ $current_datetime->format( 'Y-m-d' ) ][ '00:00' ] = 0;
      $current_datetime->add( new \DateInterval( 'P1D' ) );
    }
    
    // now incorperate operator shifts
    $modifier = new db\modifier();
    $modifier->where( 'site_id', '=', $db_site->id );
    $modifier->where( 'date', '>=', $this->start_date );
    $modifier->where( 'date', '<', $this->end_date );
    $modifier->order( 'date' );

    foreach( db\shift::select( $modifier ) as $db_shift )
    {
      // cut off seconds from start and end time
      $start = substr( $db_shift->start_time, 0, -3 );
      $start_obj = new \DateTime( $start );
      $end = substr( $db_shift->end_time, 0, -3 );

      // loop through this day's times until we pass the start time
      foreach( $days[ $db_shift->date ] as $time => $slots )
      {
        $diff = $start_obj->diff( new \DateTime( $time ) );

        if( 0 == $diff->h && 0 == $diff->i )
        { // equal to the current time
          
        }
        else if( $start_obj->diff( new \DateTime( $time ) )->invert )
        { // passed the current time
        }
      }
    }

    return array();
    /*
    $modifier = new db\modifier();
    $modifier->where( 'date', '>=', $this->start_date );
    $modifier->where( 'date', '<', $this->end_date );
    $modifier->order( 'date' );
    
    $event_list = array();


    $current_datetime = new \DateTime( $this->start_date );
    foreach( db\appointment::select_for_site( $db_site, $modifier ) as $db_appointment )
    {
      // fill time up to the appointment's date
      $appointment_datetime = new \DateTime( $db_appointment->date );
      $interval = $current_datetime->diff( $appointment_datetime );
      if( $interval->days )
      {
      }

      // set the current time to the start of this appointment
      $current_datetime = $appointment_datetime;
    }

    // fill time up to the end of the time period
    $event_list[] = array(
      'title' => 'Slots: '.$available,
      'allDay' => true,
      'start' => $current_datetime->getTimestamp(),
      'end' => strtotime( $this->end_date );

    return $event_list;
    */
  }
}
?>
