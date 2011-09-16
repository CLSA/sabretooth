<?php
/**
 * appointment.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\database
 * @filesource
 */

namespace sabretooth\database;
use sabretooth\log, sabretooth\util;
use sabretooth\business as bus;
use sabretooth\exception as exc;

/**
 * appointment: record
 *
 * @package sabretooth\database
 */
class appointment extends record
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
      $modifier = new modifier();
      $modifier->where( 'participant_id', '=', $this->participant_id );
      $modifier->where( 'assignment_id', '=', NULL );
      if( !is_null( $this->id ) ) $modifier->where( 'id', '!=', $this->id );
      if( 0 < static::count( $modifier ) )
        throw new exc\runtime(
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
      throw new exc\runtime(
        'Cannot validate appointment date, participant id is not set.', __METHOD__ );

    $db_participant = new participant( $this->participant_id );
    $db_site = $db_participant->get_primary_site();
    if( is_null( $db_site ) )
      throw new exc\runtime(
        'Cannot validate an appointment date, participant has no primary address.', __METHOD__ );
    
    // determine the appointment interval
    $interval = sprintf( 'PT%dM',
                         bus\setting_manager::self()->get_setting( 'appointment', 'duration' ) );

    $start_datetime_obj = util::get_datetime_object( $this->datetime );
    $end_datetime_obj = clone $start_datetime_obj;
    $end_datetime_obj->add( new \DateInterval( $interval ) ); // appointments are one hour long

    // determine whether to test for shifts or shift templates on the appointment day
    $modifier = new modifier();
    $modifier->where( 'site_id', '=', $db_site->id );
    $modifier->where( 'DATE( start_datetime )', '=', $start_datetime_obj->format( 'Y-m-d' ) );
    
    $diffs = array();

    if( 0 == shift::count( $modifier ) )
    { // determine slots using shift template
      $modifier = new $modifier();
      $modifier->where( 'site_id', '=', $db_site->id );
      $modifier->where( 'start_date', '<=', $start_datetime_obj->format( 'Y-m-d' ) );
      
      foreach( shift_template::select( $modifier ) as $db_shift_template )
      {
        if( $db_shift_template->match_date( $start_datetime_obj->format( 'Y-m-d' ) ) )
        {
          $start_time_as_int =
            intval( preg_replace( '/[^0-9]/', '',
              substr( $db_shift_template->start_time, 0, -3 ) ) );
          if( !array_key_exists( $start_time_as_int, $diffs ) ) $diffs[$start_time_as_int] = 0;
          $diffs[$start_time_as_int] += $db_shift_template->operators;

          $end_time_as_int =
            intval( preg_replace( '/[^0-9]/', '',
              substr( $db_shift_template->end_time, 0, -3 ) ) );
          if( !array_key_exists( $end_time_as_int, $diffs ) ) $diffs[$end_time_as_int] = 0;
          $diffs[$end_time_as_int] -= $db_shift_template->operators;
        }
      }
    }
    else // determine slots using shifts
    {
      $modifier = new $modifier();
      $modifier->where( 'site_id', '=', $db_site->id );
      $modifier->where( 'start_datetime', '<', $end_datetime_obj->format( 'Y-m-d H:i:s' ) );
      $modifier->where( 'end_datetime', '>', $start_datetime_obj->format( 'Y-m-d H:i:s' ) );

      foreach( shift::select( $modifier ) as $db_shift )
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
    $modifier = new modifier();
    $modifier->where( 'DATE( datetime )', '=', $start_datetime_obj->format( 'Y-m-d' ) );
    if( !is_null( $this->id ) ) $modifier->where( 'appointment.id', '!=', $this->id );
    foreach( appointment::select_for_site( $db_site, $modifier ) as $db_appointment )
    {
      $state = $db_appointment->get_state();
      if( 'reached' != $state && 'not reached' != $state )
      { // incomplete appointments only
        $appointment_datetime_obj = util::get_datetime_object( $db_appointment->datetime );
  
        $start_time_as_int = intval( $appointment_datetime_obj->format( 'Gi' ) );
        // increment slot one hour later
        $appointment_datetime_obj->add( new \DateInterval( $interval ) );
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
    $times = array( 0 => 0 ); // start day with no operators
    ksort( $diffs );
    $num_operators = 0;
    foreach( $diffs as $time => $diff )
    {
      $num_operators += $diff;
      $times[$time] = $num_operators;
    }
    
    // Now search the times array for any 0's inside the appointment time
    // NOTE: we need to include the time immediately prior to the appointment start time
    $start_time_as_int = intval( $start_datetime_obj->format( 'Gi' ) );
    $end_time_as_int = intval( $end_datetime_obj->format( 'Gi' ) );
    $match = false;
    $last_slots = 0;
    $last_time = 0;

    foreach( $times as $time => $slots )
    {
      if( $start_time_as_int <= $time && $time < $end_time_as_int )
      {
        if( 1 > $slots ) return false;

        if( !$match )
        {
          if( $time != $start_time_as_int && 1 > $last_slots ) return false;
          $match = true;
        }
      }

      if( $start_time_as_int <= $time && $end_time_as_int <= $time )
      { // we have passed both the start and end time
        return $start_time_as_int >= $last_time && 1 <= $last_slots;
      }

      $last_slots = $slots;
      $last_time = $time;
    }

    // make sure the last time has at least one slot
    return 1 <= $slots;
  }

  /**
   * Identical to the parent's select method but restrict to a particular site.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param site $db_site The site to restrict the selection to.
   * @param modifier $modifier Modifications to the selection.
   * @param boolean $count If true the total number of records instead of a list
   * @return array( record ) | int
   * @static
   * @access public
   */
  public static function select_for_site( $db_site, $modifier = NULL, $count = false )
  {
    // if there is no site restriction then just use the parent method
    if( is_null( $db_site ) ) return parent::select( $modifier, $count );
    
    $select_tables = 'appointment, participant_primary_address, participant, address';
    
    // straight join the tables
    if( is_null( $modifier ) ) $modifier = new modifier();
    $modifier->where(
      'appointment.participant_id', '=', 'participant_primary_address.participant_id', false );
    $modifier->where( 'participant_primary_address.address_id', '=', 'address.id', false );
    $modifier->where( 'appointment.participant_id', '=', 'participant.id', false );

    $sql = sprintf( ( $count ? 'SELECT COUNT( %s.%s ) ' : 'SELECT %s.%s ' ).
                    'FROM %s '.
                    'WHERE ( participant.site_id = %d '.
                    '  OR address.region_id IN '.
                    '  ( SELECT id FROM region WHERE site_id = %d ) ) %s',
                    static::get_table_name(),
                    static::get_primary_key_name(),
                    $select_tables,
                    $db_site->id,
                    $db_site->id,
                    $modifier->get_sql( true ) );

    if( $count )
    {
      return intval( static::db()->get_one( $sql ) );
    }
    else
    {
      $id_list = static::db()->get_col( $sql );
      $records = array();
      foreach( $id_list as $id ) $records[] = new static( $id );
      return $records;
    }
  }

  /**
   * Identical to the parent's count method but restrict to a particular site.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param site $db_site The site to restrict the count to.
   * @param modifier $modifier Modifications to the count.
   * @return int
   * @static
   * @access public
   */
  public static function count_for_site( $db_site, $modifier = NULL )
  {
    return static::select_for_site( $db_site, $modifier, true );
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

    $status = 'unknown';
    
    // settings are in minutes, time() is in seconds, so multiply by 60
    $pre_window_time = 60 * bus\setting_manager::self()->get_setting(
                              'appointment', 'call pre-window' );
    $post_window_time = 60 * bus\setting_manager::self()->get_setting(
                               'appointment', 'call post-window' );
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
        $modifier = new modifier();
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
?>
