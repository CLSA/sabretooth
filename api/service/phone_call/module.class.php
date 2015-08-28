<?php
/**
 * module.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\service\phone_call;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * Performs operations which effect how this module is used in a service
 */
class module extends \cenozo\service\module
{
  /**
   * Extend parent method
   */
  public function validate()
  {
    parent::validate();

    $service_class_name = lib::get_class_name( 'service\service' );
    $db_user = lib::create( 'business\session' )->get_user();
    $db_role = lib::create( 'business\session' )->get_role();

    $method = $this->get_method();
    if( 'PATCH' == $method &&
        $this->get_argument( 'close', false ) &&
        !array_key_exists( 'status', $this->get_file_as_array() ) )
    {
      // can't close a phone call without defining the status
      $this->set_data( 'Cannot close a phone call without specifying the status.' );
      $this->get_status()->set_code( 400 );
    }
    else if( ( 'DELETE' == $method || 'PATCH' == $method ) &&
             3 > $db_role->tier &&
             $this->get_resource()->get_assignment()->user_id != $db_user->id )
    {
      // only admins can delete or modify phone calls other than their own
      $this->get_status()->set_code( 403 );
    }
    else if( 'POST' == $method )
    {
      // do not allow more than one open phone_call
      $data = NULL;

      if( !$db_user->has_open_assignment() )
        $data = 'Cannot create a new phone call since there is no open assignment.';
      else if( $db_user->has_open_phone_call() )
        $data = 'Cannot create a new phone call since you already have one open.';
      
      if( !is_null( $data ) )
      {
        $this->set_data( $data );
        $this->get_status()->set_code( 409 );
      }
    }
  }

  /**
   * Extend parent method
   */
  public function prepare_read( $select, $modifier )
  {
    parent::prepare_read( $select, $modifier );

    $session = lib::create( 'business\session' );

    // restrict to participants in this site (for some roles)
    if( !$session->get_role()->all_sites )
    {
      $modifier->join( 'assignment', 'phone_call.assignment_id', 'assignment.id' );
      $modifier->where( 'assignment.site_id', '=', $session->get_site()->id );
    }

    if( $select->has_table_columns( 'phone' ) )
      $modifier->join( 'phone', 'phone_call.phone_id', 'phone.id' );
  }

  /**
   * Extend parent method
   */
  public function pre_write( $record )
  {
    parent::pre_write( $record );

    $method = $this->get_method();
    if( 'POST' == $method && $this->get_argument( 'open', false ) )
    {
      $session = lib::create( 'business\session' );
      $db_user = $session->get_user();

      $post_object = $this->get_file_as_object();
      $record->assignment_id = $db_user->get_open_assignment()->id;
      $record->phone_id = $post_object->phone_id;
      $record->start_datetime = util::get_datetime_object()->format( 'Y-m-d H:i:s' );
    }
    else if( 'PATCH' == $method )
    {
      if( $this->get_argument( 'close', false ) )
      { // close the phone call by setting the end datetime
        if( !is_null( $record->end_datetime ) )
        {
          log::warning( sprintf( 'Tried to close phone call id %d which is already closed.', $record->id ) );
        }
        else
        {
          $record->end_datetime = util::get_datetime_object()->format( 'Y-m-d H:i:s' );
        }
      }
    }
  }

  /**
   * Extend parent method
   */
  public function post_write( $record )
  {
    parent::post_write( $record );

    if( 'POST' == $this->get_method() && $this->get_argument( 'open', false ) )
    {
      $db_assignment = $record->get_assignment();
      $db_queue = $db_assignment->get_queue();

      // set the assignment in appointments and callbacks
      if( $db_queue->from_appointment() || $db_queue->from_callback() )
      {
        $db_interview = $db_assignment->get_interview();
        $modifier = lib::create( 'database\modifier' );
        $modifier->where( 'assignment_id', '=', NULL );
        $record_list = $db_queue->from_appointment()
                     ? $db_interview->get_appointment_object_list( $modifier )
                     : $db_interview->get_callback_object_list( $modifier );
        if( count( $record_list ) )
        {
          $linked_record = current( $record_list );
          $linked_record->assignment_id = $db_assignment->id;
          $linked_record->save();
        }
      }
    }
    else if( 'PATCH' == $this->get_method() && $this->get_argument( 'close', false ) )
    {
      $now = util::get_datetime_object()->format( 'Y-m-d H:i:s' );
      $db_interview = $record->get_assignment()->get_interview();
      $db_participant = $db_interview->get_participant();
      $db_qnaire = $db_interview->get_qnaire();

      // mark first attempt events
      $event_mod = lib::create( 'database\modifier' );
      $event_mod->where( 'event_type_id', '=', $db_qnaire->first_attempt_event_type_id );
      if( 0 == $db_participant->get_event_count( $event_mod ) )
      {
        $db_event = lib::create( 'database\event' );
        $db_event->participant_id = $db_participant->id;
        $db_event->event_type_id = $db_qnaire->first_attempt_event_type_id;
        $db_event->datetime = $now;
        $db_event->save();
      }

      // mark reached events
      $event_mod = lib::create( 'database\modifier' );
      $event_mod->where( 'event_type_id', '=', $db_qnaire->reached_event_type_id );
      if( 'contacted' == $record->status && 0 == $db_participant->get_event_count( $event_mod ) )
      {
        $db_event = lib::create( 'database\event' );
        $db_event->participant_id = $db_participant->id;
        $db_event->event_type_id = $db_qnaire->reached_event_type_id;
        $db_event->datetime = $now;
        $db_event->save();
      }
    }
  }
}
