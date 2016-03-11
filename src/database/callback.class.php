<?php
/**
 * callback.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\database;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * callback: record
 */
class callback extends \cenozo\database\record
{
  /**
   * Overrides the parent save method.
   * @author Patrick Emond
   * @access public
   */
  public function save()
  {
    $appointment_class_name = lib::get_class_name( 'database\appointment' );

    // make sure there is a maximum of 1 unassigned appointment or callback per interview
    if( is_null( $this->id ) && is_null( $this->assignment_id ) )
    {
      $callback_mod = lib::create( 'database\modifier' );
      $callback_mod->where( 'interview_id', '=', $this->interview_id );
      $callback_mod->where( 'assignment_id', '=', NULL );
      if( !is_null( $this->id ) ) $callback_mod->where( 'id', '!=', $this->id );

      $appointment_mod = lib::create( 'database\modifier' );
      $appointment_mod->where( 'interview_id', '=', $this->interview_id );
      $appointment_mod->where( 'assignment_id', '=', NULL );

      if( 0 < static::count( $appointment_mod ) || 0 < $appointment_class_name::count( $appointment_mod ) )
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
   * Get the state of the callback as a string:
   *   reached: the callback was met and the participant was reached
   *   not reached: the callback was met but the participant was not reached
   *   upcoming: the callback's date/time has not yet occurred
   *   assignable: the callback is ready to be assigned, but hasn't been
   *   incomplete: the callback was assigned but the assignment never closed (an error)
   *   assigned: the callback is currently assigned
   *   in progress: the callback is currently assigned and currently in a call
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return string
   * @access public
   */
  public function get_state( $ignore_assignments = false )
  {
    if( is_null( $this->id ) )
    {
      log::warning( 'Tried to determine state for callback with no primary key.' );
      return NULL;
    }

    // if the callback's reached column is set, nothing else matters
    if( !is_null( $this->reached ) ) return $this->reached ? 'reached' : 'not reached';

    $db_participant = lib::create( 'database\participant', $this->get_interview()->participant_id );
    $status = 'unknown';

    // settings are in minutes, time() is in seconds, so multiply by 60
    $now = util::get_datetime_object()->getTimestamp();
    $callback = $this->datetime->getTimestamp();

    // get the status of the callback
    $db_assignment = $this->get_assignment();
    if( !$ignore_assignments && !is_null( $db_assignment ) )
    {
      if( !is_null( $db_assignment->end_datetime ) )
      { // assignment closed but callback never completed
        log::crit(
          sprintf( 'Callback %d has assignment which is closed but no status was set.',
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
    else if( $now < $callback )
    {
      $status = 'upcoming';
    }
    else
    {
      $status = 'assignable';
    }

    return $status;
  }
}
