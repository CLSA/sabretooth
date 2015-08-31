<?php
/**
 * module.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\service\assignment;
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
        0 < count( $this->get_file_as_array() ) )
    {
      $this->set_data( 'Patch data must be empty when closing an assignment.' );
      $this->get_status()->set_code( 400 );
    }
    else if( ( 'DELETE' == $method || 'PATCH' == $method ) &&
        3 > $db_role->tier &&
        $this->get_resource()->user_id != $db_user->id )
    {
      // only admins can delete or modify assignments other than their own
        $this->get_status()->set_code( 403 );
    }
    else if( 'POST' == $method )
    {
      // do not allow more than one open assignment
      $data = NULL;

      if( $db_user->has_open_assignment() )
      {
        $data = 'Cannot create a new assignment since you already have one open.';
      }
      else
      {
        // repopulate the participant to make sure they are still available for an assignment
        $post_object = $this->get_file_as_object();
        $db_participant = lib::create( 'database\participant', $post_object->participant_id );
        $db_participant->update_queue_status();
        $queue_mod = lib::create( 'database\modifier' );
        $queue_mod->where( 'queue.rank', '!=', NULL );
        if( 0 == $db_participant->get_queue_count( $queue_mod ) )
        {
          $data = 'The participant is no longer available for an interview.';
        }
        else
        {
          if( !is_null( $db_participant->get_current_assignment() ) )
            $data = 'Cannot create a new assignment since the participant is already '.
                    'assigned to a different user.';
        }
      }

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
      $modifier->where( 'assignment.site_id', '=', $session->get_site()->id );

    if( $select->has_table_columns( 'queue' ) )
      $modifier->left_join( 'queue', 'assignment.queue_id', 'queue.id' );

    if( $select->has_table_columns( 'user' ) )
      $modifier->left_join( 'user', 'assignment.user_id', 'user.id' );

    if( $select->has_table_columns( 'participant' ) ||
        $select->has_table_columns( 'qnaire' ) ||
        $select->has_table_columns( 'script' ) )
    {
      $modifier->join( 'interview', 'assignment.interview_id', 'interview.id' );
      if( $select->has_table_columns( 'participant' ) )
        $modifier->join( 'participant', 'interview.participant_id', 'participant.id' );
      if( $select->has_table_columns( 'qnaire' ) || $select->has_table_columns( 'script' ) )
        $modifier->join( 'qnaire', 'interview.qnaire_id', 'qnaire.id' );
      if( $select->has_table_columns( 'script' ) )
        $modifier->join( 'script', 'qnaire.script_id', 'script.id' );
    }

    if( $select->has_column( 'phone_call_count' ) )
    {
      $join_sel = lib::create( 'database\select' );
      $join_sel->from( 'phone_call' );
      $join_sel->add_column( 'assignment_id' );
      $join_sel->add_column( 'COUNT( * )', 'phone_call_count', false );

      $join_mod = lib::create( 'database\modifier' );
      $join_mod->group( 'assignment_id' );

      $modifier->left_join(
        sprintf( '( %s %s ) AS assignment_join_phone_call', $join_sel->get_sql(), $join_mod->get_sql() ),
        'assignment.id',
        'assignment_join_phone_call.assignment_id' );
      $select->add_column( 'IFNULL( phone_call_count, 0 )', 'phone_call_count', false );
    }

    // add the assignment's last call's status column
    $modifier->left_join( 'assignment_last_phone_call',
      'assignment.id', 'assignment_last_phone_call.assignment_id' );
    $modifier->left_join( 'phone_call AS last_phone_call',
      'assignment_last_phone_call.phone_call_id', 'last_phone_call.id' );
    $select->add_table_column( 'last_phone_call', 'status' );

    if( $select->has_column( 'call_active' ) )
      $select->add_table_column( 'last_phone_call',
        'last_phone_call.id IS NOT NULL AND last_phone_call.end_datetime IS NULL',
        'call_active', false, 'boolean' );
  }

  /**
   * Extend parent method
   */
  public function pre_write( $record )
  {
    parent::pre_write( $record );

    if( 'POST' == $this->get_method() && $this->get_argument( 'open', false ) )
    {
      $session = lib::create( 'business\session' );
      $db_user = $session->get_user();
      $db_site = $session->get_site();

      // use the uid parameter to fill in the record columns
      $post_object = $this->get_file_as_object();
      $db_participant = lib::create( 'database\participant', $post_object->participant_id );
      $db_interview = $db_participant->get_effective_interview();

      $record->user_id = $db_user->id;
      $record->site_id = $db_site->id;
      $record->interview_id = $db_interview->id;
      $record->queue_id = $db_participant->current_queue_id;
      $record->start_datetime = util::get_datetime_object()->format( 'Y-m-d H:i:s' );
    }
    else if( 'PATCH' == $this->get_method() )
    {
      if( $this->get_argument( 'close', false ) )
      { // close the assignment by setting the end datetime
        if( !is_null( $record->end_datetime ) )
          log::warning( sprintf( 'Tried to close assignment id %d which is already closed.', $record->id ) );
        else $record->end_datetime = util::get_datetime_object()->format( 'Y-m-d H:i:s' );
      }
    }
  }

  /**
   * Extend parent method
   */
  public function post_write( $record )
  {
    parent::post_write( $record );

    if( 'PATCH' == $this->get_method() )
    {
      // delete the assignment if there are no phone calls, or close it if there are
      if( $this->get_argument( 'close', false ) )
      {
        if( 0 == $record->get_phone_call_count() ) $record->delete();
        else
        {
          $db_queue = $record->get_queue();

          // set reached in appointments and callbacks
          if( $db_queue->from_appointment() || $db_queue->from_callback() )
          {
            $record_list = $db_queue->from_appointment()
                         ? $record->get_appointment_object_list()
                         : $record->get_callback_object_list();
            if( 0 == count( $record_list ) )
              log::warning( sprintf(
                'Can\'t find %s for assignment %d created from %s queue',
                $db_queue->from_appointment() ? 'appointment' : 'callback',
                $record->id,
                $db_queue->name ) );
            else
            {
              $linked_record = current( $record_list );
              $modifier = lib::create( 'database\modifier' );
              $modifier->where( 'status', '=', 'contacted' );
              $linked_record->reached = 0 < $record->get_phone_call_count( $modifier );
              $linked_record->save();
            }
          }
        }
      }
    }
  }
}
