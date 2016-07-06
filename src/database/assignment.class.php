<?php
/**
 * assignment.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\database;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * assignment: record
 */
class assignment extends \cenozo\database\record
{
  /**
   * Overrides the parent save method.
   * @author Patrick Emond
   * @access public
   */
  public function save()
  {
    if( !is_null( $this->interview_id ) && is_null( $this->end_datetime ) )
    {
      // make sure there is a maximum of 1 unfinished assignment per user and interview
      $modifier = lib::create( 'database\modifier' );
      $modifier->where( 'interview_id', '=', $this->interview_id );
      $modifier->where( 'end_datetime', '=', NULL );
      if( 0 < static::count( $modifier ) )
        throw lib::create( 'exception\runtime',
          'Cannot have more than one active assignment per interview.', __METHOD__ );

      $modifier = lib::create( 'database\modifier' );
      $modifier->where( 'user_id', '=', $this->user_id );
      $modifier->where( 'end_datetime', '=', NULL );
      if( 0 < static::count( $modifier ) )
        throw lib::create( 'exception\runtime',
          'Cannot have more than one active assignment per user.', __METHOD__ );
    }

    parent::save();
    $this->get_interview()->get_participant()->repopulate_queue( true );
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
   * TODO: document
   */
  function has_open_phone_call()
  {
    // check the primary key value
    if( is_null( $this->id ) )
    {
      log::warning( 'Tried to determine if assignment with no primary key has an open phone_call.' );
      return NULL;
    }

    $phone_call_mod = lib::create( 'database\modifier' );
    $phone_call_mod->where( 'phone_call.end_datetime', '=', NULL );
    return 0 < $this->get_phone_call_count( $phone_call_mod );
  }

  /**
   * TODO: document
   */
  function get_open_phone_call()
  {
    // check the primary key value
    if( is_null( $this->id ) )
    {
      log::warning( 'Tried to get open phone_call from assignment with no primary key.' );
      return NULL;
    }

    $phone_call_mod = lib::create( 'database\modifier' );
    $phone_call_mod->where( 'phone_call.end_datetime', '=', NULL );
    $phone_call_mod->order_desc( 'phone_call.start_datetime' );
    $phone_call_list = $this->get_phone_call_object_list( $phone_call_mod );
    if( 1 < count( $phone_call_list ) )
      log::warning( sprintf( 'User %d (%s) has more than one open phone_call!', $this->id, $this->name ) );
    return 0 < count( $phone_call_list ) ? current( $phone_call_list ) : NULL;
  }

  // TODO: document
  // @param boolean $completed Whether the assignment is being closed
  function process_appointments_and_callbacks( $completed )
  {
    $db_queue = $this->get_queue();
    $db_interview = $this->get_interview();
    $db_participant = $db_interview->get_participant();

    // set the assignment and reached columns in appointments
    if( $db_queue->from_appointment() )
    {
      $modifier = lib::create( 'database\modifier' );
      $modifier->order_desc( 'datetime' );

      // if the assignment is complete then the appointment is already associated with it
      if( $completed ) $modifier->where( 'assignment_id', '=', $this->id );
      // if the assignmetn is not complete then we have to find the unassigned appointment
      else $modifier->where( 'assignment_id', '=', NULL );

      $appointment_list = $db_interview->get_appointment_object_list( $modifier );
      if( count( $appointment_list ) )
      {
        $db_appointment = current( $appointment_list );

        // if the assignment is complete then set the appointment's reached property
        if( $completed )
        {
          $modifier = lib::create( 'database\modifier' );
          $modifier->where( 'status', '=', 'contacted' );
          $db_appointment->reached = 0 < $this->get_phone_call_count( $modifier );
        }
        // if the assignment is not complete then just set the appointment's assignment
        else $db_appointment->assignment_id = $this->id;
        $db_appointment->save();
      }
    }

    // delete the participant's callback if it has passed
    if( !is_null( $db_participant->callback ) && $db_participant->callback < util::get_datetime_object() )
    {
      $db_participant->callback = NULL;
      $db_participant->save();
    }
  }
}
