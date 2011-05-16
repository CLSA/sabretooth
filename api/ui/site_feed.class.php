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
    $start_datetime = util::get_datetime_object( $this->start_date );
    $end_datetime = util::get_datetime_object( $this->end_date );
    
    $days = array();
    $current_datetime = clone $start_datetime;
    while( $current_datetime->diff( $end_datetime )->days )
    {
      $days[ $current_datetime->format( 'Y-m-d' ) ] = array(
        'slots' => array(),
        'times' => array() );
      $current_datetime->add( new \DateInterval( 'P1D' ) );
    }
    
    // then, incorperate operator shifts
    $modifier = new db\modifier();
    $modifier->where( 'site_id', '=', $db_site->id );
    $modifier->where( 'date', '>=', $this->start_date );
    $modifier->where( 'date', '<', $this->end_date );
    $modifier->order( 'date' );

    foreach( db\shift::select( $modifier ) as $db_shift )
    {
      $slots = &$days[ $db_shift->date ]['slots'];

      // increment slot at start time
      $time = intval( preg_replace( '/[^0-9]/', '', substr( $db_shift->start_time, 0, -3 ) ) );
      if( !array_key_exists( $time, $slots ) )
        $slots[$time] = array( 'operator' => 0, 'appointment' => 0 );
      $slots[$time]['operator']++;

      // decrement slot at end time
      $time = intval( preg_replace( '/[^0-9]/', '', substr( $db_shift->end_time, 0, -3 ) ) );
      if( !array_key_exists( $time, $slots ) )
        $slots[$time] = array( 'operator' => 0, 'appointment' => 0 );
      $slots[$time]['operator']--;
    }

    // then, incorperate participant appointments
    $modifier = new db\modifier();
    $modifier->where( 'date', '>=', $start_datetime->format( 'Y-m-d H:i:s' ) );
    $modifier->where( 'date', '<', $end_datetime->format( 'Y-m-d H:i:s' ) );
    $modifier->order( 'date' );

    foreach( db\appointment::select_for_site( $db_site, $modifier ) as $db_appointment )
    {
      $appointment_datetime = util::get_datetime_object( $db_appointment->date );
      $slots = &$days[ $appointment_datetime->format( 'Y-m-d' ) ]['slots'];

      // decrement slot at start time
      $time = intval( preg_replace( '/[^0-9]/', '', $appointment_datetime->format( 'H:i' ) ) );
      if( !array_key_exists( $time, $slots ) )
        $slots[$time] = array( 'operator' => 0, 'appointment' => 0 );
      $slots[$time]['appointment']++;

      // increment slot one hour later
      $appointment_datetime->add( new \DateInterval( 'PT1H' ) );
      $time = intval( preg_replace( '/[^0-9]/', '', $appointment_datetime->format( 'H:i' ) ) );
      if( !array_key_exists( $time, $slots ) )
        $slots[$time] = array( 'operator' => 0, 'appointment' => 0 );
      $slots[$time]['appointment']--;
    }

    // then, define the 'times' array to indicate when the number of slots changes, making sure to
    // incorperate the site's expected slots and filled slots
    $db_setting = db\setting::get_setting( 'appointment', 'start_time' );
    $expected_start = intval( preg_replace( '/[^0-9]/', '', $db_setting->value ) );
    $db_setting = db\setting::get_setting( 'appointment', 'end_time' );
    $expected_end = intval( preg_replace( '/[^0-9]/', '', $db_setting->value ) );
    $expected_slots = $db_site->operators_expected;

    foreach( $days as $date => $day )
    {
      $num_operators = 0;
      $num_appointments = 0;
      $slots = &$days[$date]['slots'];
      $times = &$days[$date]['times'];
      $passed_expected_start = false;
      $passed_expected_end = false;
      $time = $expected_start; // in case there are no slots for this day

      // sort the slots array by key (time) to make the following for loop nice and simple
      ksort( $slots );

      foreach( $slots as $time => $deltas )
      {
        $prev_num_appointments = $num_appointments;
        $prev_num_operators = $num_operators;
        $num_operators += $deltas['operator'];
        $num_appointments += $deltas['appointment'];
        
        if( 0 >= $expected_slots )
        {
          $open_slots = $num_operators - $num_appointments;
          if( 0 > $open_slots ) $open_slots = 0;
          $times[$time] = $open_slots;
        }
        else
        {
          if( !$passed_expected_start && $time > $expected_start )
          { // just passed the expected start time
            if( $prev_num_operators < $expected_slots &&
                !array_key_exists( $expected_start, $times ) )
            {
              $open_slots = $expected_slots - $prev_num_appointments;
              if( 0 > $open_slots ) $open_slots = 0;
              $times[$expected_start] = $open_slots;
            }
            $passed_expected_start = true;
          }

          if( !$passed_expected_end && $time > $expected_end )
          { // just passed the expected end time
            if( $prev_num_operators < $expected_slots &&
                !array_key_exists( $expected_end, $times ) )
            {
              $open_slots = $prev_num_operators - $prev_num_appointments;
              if( 0 > $open_slots ) $open_slots = 0;
              $times[$expected_end] = $open_slots;
            }
            $passed_expected_end = true;
          }
          
          $open_slots = $expected_slots > $num_operators &&
                       $expected_start <= $time && $time <= $expected_end
                     ? $expected_slots : $num_operators;
          $open_slots -= $num_appointments;
          if( 0 > $open_slots ) $open_slots = 0;
          $times[$time] = $open_slots;
        }
      }

      // fill in to the end of the expected time, if there are expected slots
      if( $expected_slots && $time < $expected_end )
      {
        $times[$time] = $expected_slots;
        $times[$expected_end] = 0;
      }
    }

    // finally, construct the event list using the 'times' array
    $start_time = false;
    $available = 0;
    $event_list = array();
    foreach( $days as $date => $day )
    {
      foreach( $day['times'] as $time => $number )
      {
        if( $number == $available ) continue;

        $minutes = $time % 100;
        $hours = ( $time - $minutes ) / 100;
        $time_string = sprintf( '%02d:%02d', $hours, $minutes );
        if( $start_time )
        {
          $end_time = $time_string;
          
          if( $available )
          {
            $event_list[] = array(
              'title' => 'Slots: '.$available,
              'allDay' => false,
              'start' => $date.' '.$start_time,
              'end' => $date.' '.$end_time );
          }
        }

        // only use this time as the next start time if the available number is not 0
        $start_time = 0 < $number ? $time_string : false;
        $available = $number;
      }
    }
    
    return $event_list;
  }
}
?>
