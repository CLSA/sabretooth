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
      log::warning( 'Tried to determine if assignment with no id has an open phone_call.' );
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
      log::warning( 'Tried to get open phone_call from assignment with no id.' );
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
  function process_appointments_and_callbacks()
  {
    $db_queue = $this->get_queue();

    // set the assignment and reached in appointments and callbacks
    if( $db_queue->from_appointment() || $db_queue->from_callback() )
    {
      $db_interview = $this->get_interview();
      $modifier = lib::create( 'database\modifier' );
      $modifier->where( 'assignment_id', '=', NULL );
      $record_list = $db_queue->from_appointment()
                   ? $db_interview->get_appointment_object_list( $modifier )
                   : $db_interview->get_callback_object_list( $modifier );
      if( count( $record_list ) )
      {
        $linked_record = current( $record_list );
        $modifier = lib::create( 'database\modifier' );
        $modifier->where( 'status', '=', 'contacted' );
        $linked_record->reached = 0 < $record->get_phone_call_count( $modifier );
        $linked_record->assignment_id = $db_assignment->id;
        $linked_record->save();
      }
    }
  }
}
