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
   * Overrides the parent save method.
   * @author Patrick Emond
   * @access public
   */
  public function save()
  {
    $callback_class_name = lib::get_class_name( 'database\callback' );

    // make sure there is a maximum of 1 unassigned appointment per interview
    if( is_null( $this->id ) && is_null( $this->assignment_id ) )
    {
      $appointment_mod = lib::create( 'database\modifier' );
      $appointment_mod->where( 'interview_id', '=', $this->interview_id );
      $appointment_mod->where( 'assignment_id', '=', NULL );
      if( !is_null( $this->id ) ) $appointment_mod->where( 'id', '!=', $this->id );

      $callback_mod = lib::create( 'database\modifier' );
      $callback_mod->where( 'interview_id', '=', $this->interview_id );
      $callback_mod->where( 'assignment_id', '=', NULL );

      if( 0 < static::count( $appointment_mod ) || 0 < $callback_class_name::count( $callback_mod ) )
        throw lib::create( 'exception\notice',
          'Cannot have more than one unassigned appointment or callback per interview.', __METHOD__ );
    }

    // if we changed certain columns then update the queue
    $update_queue = $this->has_column_changed( array( 'assignment_id', 'datetime' ) );
    parent::save();
    if( $update_queue ) $this->get_interview()->get_participant()->repopulate_queue( true );
  }

  /**
   * Override the parent method
   */
  public function delete()
  {
    $db_participant = $this->get_interview()->get_participant();
    parent::delete();
    $db_participant->repopulate_queue( true );
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
    if( is_null( $this->interview_id ) )
      throw lib::create( 'exception\runtime',
        'Cannot validate appointment date, interview id is not set.', __METHOD__ );

    // TODO: rewrite method

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
  public function get_state()
  {
    if( is_null( $this->id ) )
    {
      log::warning( 'Tried to determine state for appointment with no id.' );
      return NULL;
    } 
    
    // if the appointment's reached column is set, nothing else matters
    if( !is_null( $this->reached ) ) return $this->reached ? 'reached' : 'not reached';

    $db_participant = lib::create( 'database\participant', $this->get_interview()->participant_id );
    $db_setting = $db_participant->get_effective_site()->get_setting();

    $status = 'unknown';
    
    // settings are in minutes, time() is in seconds, so multiply by 60
    $pre_window_time = 60 * $db_setting->pre_call_window;
    $post_window_time = 60 * $db_setting->post_call_window;
    $now = util::get_datetime_object()->getTimestamp();
    $appointment = $this->datetime->getTimestamp();

    // get the status of the appointment
    $db_assignment = $this->get_assignment();
    if( !is_null( $db_assignment ) )
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
