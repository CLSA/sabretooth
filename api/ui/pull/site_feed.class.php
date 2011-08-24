<?php
/**
 * site_feed.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui\pull;
use sabretooth\log, sabretooth\util;
use sabretooth\business as bus;
use sabretooth\database as db;
use sabretooth\exception as exc;

/**
 * pull: site feed
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
   * @param array $args Pull arguments.
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
  public function finish()
  {
    $db_site = bus\session::self()->get_site();

    // start by creating an array with one element per day in the time span
    $start_datetime_obj = util::get_datetime_object( $this->start_datetime );
    $end_datetime_obj = util::get_datetime_object( $this->end_datetime );
    
    $days = array();
    $current_datetime_obj = clone $start_datetime_obj;
    while( $current_datetime_obj->diff( $end_datetime_obj )->days )
    {
      $days[ $current_datetime_obj->format( 'Y-m-d' ) ] = array(
        'template' => false,
        'diffs' => array(),
        'times' => array() );
      $current_datetime_obj->add( new \DateInterval( 'P1D' ) );
    }
    
    // fill in the slot differentials for shift templates each day
    $modifier = new db\modifier();
    $modifier->where( 'site_id', '=', $db_site->id );
    $modifier->where( 'start_date', '<=', $this->end_datetime );
    foreach( db\shift_template::select( $modifier ) as $db_shift_template )
    {
      foreach( $days as $date => $day )
      {
        $diffs = &$days[$date]['diffs'];
          
        if( $db_shift_template->match_date( $date ) )
        {
          $days[$date]['template'] = true;

          $start_time_as_int =
            intval( preg_replace( '/[^0-9]/', '',
              substr( $db_shift_template->start_time, 0, -3 ) ) );
          if( !array_key_exists( $start_time_as_int, $diffs ) ) $diffs[ $start_time_as_int ] = 0;
          $diffs[ $start_time_as_int ] += $db_shift_template->operators;

          $end_time_as_int =
            intval( preg_replace( '/[^0-9]/', '',
              substr( $db_shift_template->end_time, 0, -3 ) ) );
          if( !array_key_exists( $end_time_as_int, $diffs ) ) $diffs[ $end_time_as_int ] = 0;
          $diffs[ $end_time_as_int ] -= $db_shift_template->operators;
        }

        // unset diffs since it is a reference
        unset( $diffs );
      }
    }

    // fill in the shifts (which override shift templates for that day)
    $modifier = new db\modifier();
    $modifier->where( 'site_id', '=', $db_site->id );
    $modifier->where( 'start_datetime', '<', $this->end_datetime );
    $modifier->where( 'end_datetime', '>', $this->start_datetime );
    $modifier->order( 'start_datetime' );
    foreach( db\shift::select( $modifier ) as $db_shift )
    {
      $start_datetime_obj = util::get_datetime_object( $db_shift->start_datetime );
      $end_datetime_obj = util::get_datetime_object( $db_shift->end_datetime );
      $date = $start_datetime_obj->format( 'Y-m-d' );
      
      if( $days[$date]['template'] )
      { // remove the shift templates for this day, replace with shift
        $days[$date]['diffs'] = array();
        $days[$date]['template'] = false;
      }

      $diffs = &$days[ $start_datetime_obj->format( 'Y-m-d' ) ]['diffs'];
      
      $start_time_as_int = intval( $start_datetime_obj->format( 'Gi' ) );
      $end_time_as_int = intval( $end_datetime_obj->format( 'Gi' ) );
      
      if( !array_key_exists( $start_time_as_int, $diffs ) ) $diffs[ $start_time_as_int ] = 0;
      $diffs[ $start_time_as_int ]++;
      if( !array_key_exists( $end_time_as_int, $diffs ) ) $diffs[ $end_time_as_int ] = 0;
      $diffs[ $end_time_as_int ]--;

      // unset diffs since it is a reference
      unset( $diffs );
    }

    // fill in the appointments which have not been completed
    $modifier = new db\modifier();
    $modifier->where( 'datetime', '>=', $this->start_datetime );
    $modifier->where( 'datetime', '<', $this->end_datetime );
    $modifier->order( 'datetime' );
    foreach( db\appointment::select_for_site( $db_site, $modifier ) as $db_appointment )
    {
      $state = $db_appointment->get_state();
      if( 'reached' != $state && 'not reached' != $state )
      { // incomplete appointments only
        $appointment_datetime_obj = util::get_datetime_object( $db_appointment->datetime );
        $diffs = &$days[ $appointment_datetime_obj->format( 'Y-m-d' ) ]['diffs'];
  
        $start_time_as_int = intval( $appointment_datetime_obj->format( 'Gi' ) );
        // increment slot one hour later
        $appointment_datetime_obj->add( new \DateInterval( 'PT1H' ) );
        $end_time_as_int = intval( $appointment_datetime_obj->format( 'Gi' ) );
  
        if( !array_key_exists( $start_time_as_int, $diffs ) ) $diffs[ $start_time_as_int ] = 0;
        $diffs[ $start_time_as_int ]--;
        if( !array_key_exists( $end_time_as_int, $diffs ) ) $diffs[ $end_time_as_int ] = 0;
        $diffs[ $end_time_as_int ]++;
  
        // unset diffs since it is a reference
        unset( $diffs );
      }
    }
    
    // use the 'diff' arrays to define the 'times' array
    foreach( $days as $date => $day )
    {
      $num_operators = 0;
      $diffs = &$days[$date]['diffs'];
      $times = &$days[$date]['times'];
      
      if( 0 < count( $diffs ) )
      {
        // sort the diff array by key (time) to make the following for-loop nice and simple
        ksort( $diffs );
  
        foreach( $diffs as $time => $diff )
        {
          $num_operators += $diff;
          $times[$time] = $num_operators;
        }
      }

      // unset times since it is a referece
      unset( $times );
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
            $end_time_for_title =
              sprintf( '%s%s%s',
                       $hours > 12 ? $hours - 12 : $hours,
                       $minutes ? ':'.sprintf( '%02d', $minutes ) : '',
                       $hours > 12 ? 'p' : 'a' );
            $event_list[] = array(
              'title' => sprintf( ' to %s: %d slots', $end_time_for_title, $available ),
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



/* OLD CODE *
    // start by creating an array with one element per day in the time span
    $start_datetime_obj = util::get_datetime_object( $this->start_datetime );
    $end_datetime_obj = util::get_datetime_object( $this->end_datetime );
    
    $days = array();
    $current_datetime_obj = clone $start_datetime_obj;
    while( $current_datetime_obj->diff( $end_datetime_obj )->days )
    {
      $days[ $current_datetime_obj->format( 'Y-m-d' ) ] = array(
        'slots' => array(),
        'times' => array() );
      $current_datetime_obj->add( new \DateInterval( 'P1D' ) );
    }
    
    // then, incorperate operator shifts
    $modifier = new db\modifier();
    $modifier->where( 'site_id', '=', $db_site->id );
    $modifier->where( 'end_datetime', '>', $this->start_datetime );
    $modifier->where( 'start_datetime', '<', $this->end_datetime );
    $modifier->order( 'start_datetime' );

    foreach( db\shift::select( $modifier ) as $db_shift )
    {
      $date = substr( $db_shift->start_datetime, 0, 10 );
      $start_time = substr( $db_shift->start_datetime, 11, -3 );
      $end_time = substr( $db_shift->end_datetime, 11, -3 );

      $slots = &$days[ $date ]['slots'];

      // increment slot at start time
      $time = intval( preg_replace( '/[^0-9]/', '', $start_time ) );
      if( !array_key_exists( $time, $slots ) )
        $slots[$time] = array( 'operator' => 0, 'appointment' => 0 );
      $slots[$time]['operator']++;

      // decrement slot at end time
      $time = intval( preg_replace( '/[^0-9]/', '', $end_time ) );
      if( !array_key_exists( $time, $slots ) )
        $slots[$time] = array( 'operator' => 0, 'appointment' => 0 );
      $slots[$time]['operator']--;
    }

    // then, incorperate participant appointments
    $modifier = new db\modifier();
    $modifier->where( 'datetime', '>=', $start_datetime_obj->format( 'Y-m-d H:i:s' ) );
    $modifier->where( 'datetime', '<', $end_datetime_obj->format( 'Y-m-d H:i:s' ) );
    $modifier->order( 'datetime' );

    foreach( db\appointment::select_for_site( $db_site, $modifier ) as $db_appointment )
    {
      $appointment_datetime_obj = util::get_datetime_object( $db_appointment->datetime );
      $slots = &$days[ $appointment_datetime_obj->format( 'Y-m-d' ) ]['slots'];

      // decrement slot at start time
      $time = intval( preg_replace( '/[^0-9]/', '', $appointment_datetime_obj->format( 'H:i' ) ) );
      if( !array_key_exists( $time, $slots ) )
        $slots[$time] = array( 'operator' => 0, 'appointment' => 0 );
      $slots[$time]['appointment']++;

      // increment slot one hour later
      $appointment_datetime_obj->add( new \DateInterval( 'PT1H' ) );
      $time = intval( preg_replace( '/[^0-9]/', '', $appointment_datetime_obj->format( 'H:i' ) ) );
      if( !array_key_exists( $time, $slots ) )
        $slots[$time] = array( 'operator' => 0, 'appointment' => 0 );
      $slots[$time]['appointment']--;
    }
    
    // then, define the 'times' array to indicate when the number of slots changes, making sure to
    // incorperate the site's expected slots and filled slots
    $expected_start = intval( preg_replace( '/[^0-9]/', '',
      bus\setting_manager::self()->get_setting( 'appointment', 'start_time' ) ) );
    $expected_end = intval( preg_replace( '/[^0-9]/', '',
      bus\setting_manager::self()->get_setting( 'appointment', 'end_time' ) ) );
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
OLD CODE ************************************************************************/
  }
}
?>
