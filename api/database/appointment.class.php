<?php
/**
 * appointment.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\database;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * appointment: record
 */
class appointment extends \cenozo\database\record
{
  /**
   * Overrides the parent load method.
   * @author Patrick Emond
   * @access public
   */
  public function load()
  {
    parent::load();

    // appointments are not to the second, so remove the :00 at the end of the datetime field
    $this->datetime = substr( $this->datetime, 0, -3 );
  }
  
  /**
   * Overrides the parent save method.
   * @author Patrick Emond
   * @access public
   */
  public function save()
  {
    // make sure there is a maximum of 1 unassigned appointment
    if( is_null( $this->assignment_id ) )
    {
      $modifier = lib::create( 'database\modifier' );
      $modifier->where( 'participant_id', '=', $this->participant_id );
      $modifier->where( 'assignment_id', '=', NULL );
      if( !is_null( $this->id ) ) $modifier->where( 'id', '!=', $this->id );
      if( 0 < static::count( $modifier ) )
        throw lib::create( 'exception\runtime',
          'Cannot have more than one unassigned appointment per participant.', __METHOD__ );
    }

    parent::save();
  }
  
  /**
   * Determines whether there are operator slots available during this appointment's date/time
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return boolean
   * @throws exception\runtime
   * @access public
   */
  public function validate_date()
  {
    if( is_null( $this->participant_id ) )
      throw lib::create( 'exception\runtime',
        'Cannot validate appointment date, participant id is not set.', __METHOD__ );

    $daylight_savings = '1' == util::get_datetime_object()->format( 'I' );
    $db_participant = lib::create( 'database\participant', $this->participant_id );
    $db_site = $db_participant->get_primary_site();
    if( is_null( $db_site ) )
      throw lib::create( 'exception\runtime',
        'Cannot validate an appointment date, participant has no primary address.', __METHOD__ );
    
    $shift_template_class_name = lib::get_class_name( 'database\shift_template' );
    $shift_class_name = lib::get_class_name( 'database\shift' );

    // determine the full and half appointment intervals
    $setting_manager = lib::create( 'business\setting_manager' );
    $half_duration = $setting_manager->get_setting( 'appointment', 'half duration', $db_site );
    $full_duration = $setting_manager->get_setting( 'appointment', 'full duration', $db_site );

    $start_datetime_obj = util::get_datetime_object( $this->datetime );
    $next_day_datetime_obj = clone $start_datetime_obj;
    $next_day_datetime_obj->add( new\DateInterval( 'P1D' ) );
    $end_datetime_obj = clone $start_datetime_obj;
    $duration = 'full' == $this->type ? $full_duration : $half_duration;
    $end_datetime_obj->add( new \DateInterval( sprintf( 'PT%dM', $duration ) ) );

    // determine whether to test for shifts or shift templates on the appointment day
    $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'site_id', '=', $db_site->id );
    $modifier->where( 'start_datetime', '>=', $start_datetime_obj->format( 'Y-m-d' ) );
    $modifier->where( 'start_datetime', '<', $next_day_datetime_obj->format( 'Y-m-d' ) );

    $diffs = array();

    if( 0 == $shift_class_name::count( $modifier ) )
    { // determine slots using shift template
      $modifier = lib::create( 'database\modifier' );
      $modifier->where( 'site_id', '=', $db_site->id );
      $modifier->where( 'start_date', '<=', $start_datetime_obj->format( 'Y-m-d' ) );
      foreach( $shift_template_class_name::select( $modifier ) as $db_shift_template )
      {
        if( $db_shift_template->match_date( $start_datetime_obj->format( 'Y-m-d' ) ) )
        {
          $start_time_as_int =
            intval( preg_replace( '/[^0-9]/', '',
              substr( $db_shift_template->start_time, 0, -3 ) ) );
          if( $daylight_savings ) $start_time_as_int -= 100; // adjust for daylight savings
          if( !array_key_exists( $start_time_as_int, $diffs ) ) $diffs[$start_time_as_int] = 0;
          $diffs[$start_time_as_int] += $db_shift_template->operators;

          $end_time_as_int =
            intval( preg_replace( '/[^0-9]/', '',
              substr( $db_shift_template->end_time, 0, -3 ) ) );
          if( $daylight_savings ) $end_time_as_int -= 100; // adjust for daylight savings
          if( !array_key_exists( $end_time_as_int, $diffs ) ) $diffs[$end_time_as_int] = 0;
          $diffs[$end_time_as_int] -= $db_shift_template->operators;
        }
      }
    }
    else // determine slots using shifts
    {
      $modifier = lib::create( 'database\modifier' );
      $modifier->where( 'site_id', '=', $db_site->id );
      $modifier->where( 'start_datetime', '<', $end_datetime_obj->format( 'Y-m-d H:i:s' ) );
      $modifier->where( 'end_datetime', '>', $start_datetime_obj->format( 'Y-m-d H:i:s' ) );
      foreach( $shift_class_name::select( $modifier ) as $db_shift )
      {
        $start_time_as_int =
          intval( preg_replace( '/[^0-9]/', '',
            substr( $db_shift->start_datetime, -8, -3 ) ) );
        $end_time_as_int =
          intval( preg_replace( '/[^0-9]/', '',
            substr( $db_shift->end_datetime, -8, -3 ) ) );

        if( !array_key_exists( $start_time_as_int, $diffs ) ) $diffs[$start_time_as_int] = 0;
        $diffs[$start_time_as_int]++;
        if( !array_key_exists( $end_time_as_int, $diffs ) ) $diffs[$end_time_as_int] = 0;
        $diffs[$end_time_as_int]--;
      }
    }
    
    // and how many appointments are during this time?
    $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'participant_site.site_id', '=', $db_site->id );
    $modifier->where( 'datetime', '>=', $start_datetime_obj->format( 'Y-m-d' ) );
    $modifier->where( 'datetime', '<', $next_day_datetime_obj->format( 'Y-m-d' ) );
    if( !is_null( $this->id ) ) $modifier->where( 'appointment.id', '!=', $this->id );
    $appointment_class_name = lib::get_class_name( 'database\appointment' );
    foreach( $appointment_class_name::select( $modifier ) as $db_appointment )
    {
      $state = $db_appointment->get_state();
      if( 'reached' != $state && 'not reached' != $state )
      { // incomplete appointments only
        $appointment_datetime_obj = util::get_datetime_object( $db_appointment->datetime );
  
        $start_time_as_int = intval( $appointment_datetime_obj->format( 'Gi' ) );
        
        $duration = 'full' == $db_appointment->type ? $full_duration : $half_duration;
        $appointment_datetime_obj->add( new \DateInterval( sprintf( 'PT%dM', $duration ) ) );
        $end_time_as_int = intval( $appointment_datetime_obj->format( 'Gi' ) );
  
        if( !array_key_exists( $start_time_as_int, $diffs ) ) $diffs[ $start_time_as_int ] = 0;
        $diffs[ $start_time_as_int ]--;
        if( !array_key_exists( $end_time_as_int, $diffs ) ) $diffs[ $end_time_as_int ] = 0;
        $diffs[ $end_time_as_int ]++;
      }
    }
    
    // if we have no diffs on this day, then we have no slots
    if( 0 == count( $diffs ) ) return false;

    // use the 'diff' arrays to define the 'times' array
    $times = array();
    ksort( $diffs );
    $num_operators = 0;
    foreach( $diffs as $time => $diff )
    {
      $num_operators += $diff;
      $times[$time] = $num_operators;
    }

    // end day with no operators (4800 is used because it is long after the end of the day)
    $times[4800] = 0;
    
    // Now search the times array for any 0's inside the appointment time
    // NOTE: we need to include the time immediately prior to the appointment start time
    $start_time_as_int = intval( $start_datetime_obj->format( 'Gi' ) );
    $end_time_as_int = intval( $end_datetime_obj->format( 'Gi' ) );
    $match = false;
    $last_slots = 0;
    $last_time = 0;

    foreach( $times as $time => $slots )
    {
      // check the start time
      if( $last_time <= $start_time_as_int &&
          $time > $start_time_as_int &&
          1 > $last_slots ) return false;

      // check the end time
      if( $last_time < $end_time_as_int &&
          $time >= $end_time_as_int &&
          1 > $last_slots ) return false;

      $last_slots = $slots;
      $last_time = $time;
    }
    
    return true;
  }

  /**
   * Get the state of the appointment as a string:
   *   reached: the appointment was met and the participant was reached
   *   not reached: the appointment was met but the participant was not reached
   *   upcoming: the appointment's date/time has not yet occurred
   *   assignable: the appointment is ready to be assigned, but hasn't been
   *   missed: the appointment was missed (never assigned) and the call window has passed
   *   incomplete: the appointment was assigned but the assignment never closed (an error)
   *   assigned: the appointment is currently assigned
   *   in progress: the appointment is currently assigned and currently in a call
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return string
   * @access public
   */
  public function get_state( $ignore_assignments = false )
  {
    if( is_null( $this->id ) )
    {
      log::warning( 'Tried to determine state for appointment with no id.' );
      return NULL;
    } 
    
    // if the appointment's reached column is set, nothing else matters
    if( !is_null( $this->reached ) ) return $this->reached ? 'reached' : 'not reached';

    $db_participant = lib::create( 'database\participant', $this->participant_id );
    $db_site = $db_participant->get_primary_site();

    $status = 'unknown';
    
    // settings are in minutes, time() is in seconds, so multiply by 60
    $setting_manager = lib::create( 'business\setting_manager' );
    $pre_window_time  = 60 * $setting_manager->get_setting(
                              'appointment', 'call pre-window', $db_site );
    $post_window_time = 60 * $setting_manager->get_setting(
                              'appointment', 'call post-window', $db_site );
    $now = util::get_datetime_object()->getTimestamp();
    $appointment = util::get_datetime_object( $this->datetime )->getTimestamp();

    // get the status of the appointment
    $db_assignment = $this->get_assignment();
    if( !$ignore_assignments && !is_null( $db_assignment ) )
    {
      if( !is_null( $db_assignment->end_datetime ) )
      { // assignment closed but appointment never completed
        log::crit(
          sprintf( 'Appointment %d has assignment which is closed but no status was set.',
                   $this->id ) );
        $status = 'incomplete';
      }
      else // assignment active
      {
        $modifier = lib::create( 'database\modifier' ); 
        $modifier->where( 'end_datetime', '=', NULL );
        $open_phone_calls = $db_assignment->get_phone_call_count( $modifier );
        if( 0 < $open_phone_calls )
        { // assignment currently on call
          $status = "in progress";
        }
        else
        { // not on call
          $status = "assigned";
        }
      }
    }
    else if( $now < $appointment - $pre_window_time )
    {
      $status = 'upcoming';
    }
    else if( $now < $appointment + $post_window_time )
    {
      $status = 'assignable';
    }
    else
    {
      $status = 'missed';
    }

    return $status;
  }
}

// define the join to the participant_site table
$participant_site_mod = lib::create( 'database\modifier' );
$participant_site_mod->where(
  'appointment.participant_id', '=', 'participant_site.participant_id', false );
appointment::customize_join( 'participant_site', $participant_site_mod );
?>
