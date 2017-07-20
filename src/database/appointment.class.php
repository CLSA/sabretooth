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
    // make sure there is a maximum of 1 unresolved appointment per interview
    if( is_null( $this->id ) && is_null( $this->assignment_id ) )
    {
      $appointment_mod = lib::create( 'database\modifier' );
      $appointment_mod->join( 'vacancy', 'appointment.start_vacancy_id', 'vacancy.id' );
      $appointment_mod->where( 'assignment_id', '=', NULL );
      $appointment_mod->where( 'outcome', '=', NULL );
      if( !is_null( $this->id ) ) $appointment_mod->where( 'id', '!=', $this->id );
      $appointment_mod->order( 'vacancy.datetime' );

      // cancel any missed appointments
      foreach( $this->get_interview()->get_appointment_object_list( $appointment_mod ) as $db_appointment )
      {
        $db_start_vacancy = $db_appointment->get_start_vacancy();
        if( $db_start_vacancy->datetime < util::get_datetime_object() )
        {
          $db_appointment->outcome = 'cancelled';
          $db_appointment->save();
        }
        else
        {
          throw lib::create( 'exception\notice',
            'Cannot have more than one unassigned appointment per interview.', __METHOD__ );
        }
      }
    }

    // if we changed certain columns then update the queue
    $update_queue = $this->has_column_changed( array( 'assignment_id', 'outcome' ) );
    parent::save();
    if( $update_queue ) $this->get_interview()->get_participant()->repopulate_queue( true );
  }

  /**
   * Override the parent method
   */
  public function delete()
  {
    $vacancy_class_name = lib::get_class_name( 'database\vacancy' );

    $db_participant = $this->get_interview()->get_participant();
    parent::delete();
    $vacancy_class_name::remove_defunct();
    $db_participant->repopulate_queue( true );
  }

  /**
   * Convenience method
   */
  public function get_start_vacancy()
  {
    return is_null( $this->start_vacancy_id ) ?
      NULL : lib::create( 'database\vacancy', $this->start_vacancy_id );
  }

  /**
   * Convenience method
   */
  public function get_end_vacancy()
  {
    return is_null( $this->end_vacancy_id ) ?
      NULL : lib::create( 'database\vacancy', $this->end_vacancy_id );
  }

  /**
   * TODO: document
   */
  public function get_duration()
  {
    $duration = NULL;
    $db_start_vacancy = $this->get_start_vacancy();
    $db_end_vacancy = $this->get_end_vacancy();

    if( !is_null( $db_start_vacancy ) && !is_null( $db_end_vacancy ) )
    {
      $diff = util::get_interval(
        $db_start_vacancy->datetime,
        $db_end_vacancy->datetime
      );

      // get the duration and add 30 (to include the last vacancy)
      $duration = $diff->days*1440 + $diff->h*60 + $diff->i + 30;
    }

    return $duration;
  }

  /**
   * Get the state of the appointment as a string:
   *   reached: the appointment was met and the participant was reached
   *   not reached: the appointment was met but the participant was not reached
   *   cancelled: the appointment was cancelled (never used)
   *   upcoming: the appointment's date/time has not yet occurred
   *   assignable: the appointment is ready to be assigned, but hasn't been
   *   missed: the appointment was missed (never assigned) and the call window has passed
   *   error: the appointment was assigned but the appointment's outcome was never defined (an error)
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
      log::warning( 'Tried to determine state for appointment with no primary key.' );
      return NULL;
    }

    // if the appointment's outcome column is set, nothing else matters
    if( !is_null( $this->outcome ) ) return $this->outcome;

    $db_setting = $this->get_interview()->get_participant()->get_effective_site()->get_setting();
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
        $status = 'error';
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
