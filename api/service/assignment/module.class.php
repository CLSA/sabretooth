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

    // do not allow more than one open assignment
    $method = $this->get_method();
    if( 'DELETE' == $method ||'PATCH' == $method )
    {
      // only admins can delete or modify assignments other than their own
      if( 3 > $db_role->tier && $this->get_resource()->user_id != $db_user->id )
        $this->get_status()->set_code( 403 );
    }
    else if( 'POST' == $method )
    {
      $data = NULL;

      if( $db_user->has_open_assignment() )
      {
        $data = 'Cannot create a new assignment since you already have one open.';
      }
      else
      {
        $post_object = $this->get_file_as_object();
        $db_participant = lib::create( 'database\participant', $post_object->participant_id );
        if( !is_null( $db_participant->get_current_assignment() ) )
          $data = 'Cannot create a new assignment since the participant is already assigned to a different user.';
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
    {
      $modifier->where( 'assignment.site_id', '=', $session->get_site()->id );
    }

    if( $select->has_table_columns( 'queue' ) )
      $modifier->left_join( 'queue', 'assignment.queue_id', 'queue.id' );

    if( $select->has_table_columns( 'participant' ) || $select->has_table_columns( 'qnaire' )  )
    {
      $modifier->join( 'interview', 'assignment.interview_id', 'interview.id' );
      if( $select->has_table_columns( 'participant' ) )
        $modifier->join( 'participant', 'interview.participant_id', 'participant.id' );
      if( $select->has_table_columns( 'qnaire' ) )
        $modifier->join( 'qnaire', 'interview.qnaire_id', 'qnaire.id' );
    }

    // add the assignment's last call's status column
    $modifier->left_join( 'assignment_last_phone_call',
      'assignment.id', 'assignment_last_phone_call.assignment_id' );
    $modifier->left_join( 'phone_call AS last_phone_call',
      'assignment_last_phone_call.phone_call_id', 'last_phone_call.id' );
    $select->add_table_column( 'last_phone_call', 'status' );
  }

  /**
   * Extend parent method
   */
  public function pre_write( $record )
  {
    parent::pre_write( $record );

    if( 'POST' == $this->get_method() )
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
      $patch_array = $this->get_file_as_array();
      if( 1 == count( $patch_array ) && array_key_exists( 'close', $patch_array ) && $patch_array['close'] )
      { // close the assignment by setting the end datetime
        if( is_null( $record->end_datetime ) )
          log::warning( sprintf( 'Tried to close assignment id %d which is already closed.', $record->id ) );
        else $record->end_datetime = util::get_datetime_object()->format( 'Y-m-d H:i:s' );
      }
    }
  }
}
