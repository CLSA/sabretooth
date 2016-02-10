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
class module extends \cenozo\service\site_restricted_module
{
  /**
   * Extend parent method
   */
  public function validate()
  {
    parent::validate();

    $service_class_name = lib::get_class_name( 'service\service' );
    $qnaire_class_name = lib::get_class_name( 'database\qnaire' );
    $tokens_class_name = lib::get_class_name( 'database\limesurvey\tokens' );

    $db_user = lib::create( 'business\session' )->get_user();
    $db_role = lib::create( 'business\session' )->get_role();
    $method = $this->get_method();
    $operation = $this->get_argument( 'operation', false );

    // restrict by site
    $db_restrict_site = $this->get_restricted_site();
    if( !is_null( $db_restrict_site ) )
    {
      $record = $this->get_resource();
      if( $record && $record->site_id && $record->site_id != $db_restrict_site->id )
        $this->get_status()->set_code( 403 );
    }

    if( ( 'DELETE' == $method || 'PATCH' == $method ) &&
        3 > $db_role->tier &&
        $this->get_resource()->user_id != $db_user->id )
    {
      // only admins can delete or modify assignments other than their own
        $this->get_status()->set_code( 403 );
    }
    else if( 'PATCH' == $method && ( 'advance' == $operation || 'close' == $operation ) )
    {
      $record = $this->get_resource();

      if( 0 < count( $this->get_file_as_array() ) )
      {
        $this->set_data( 'Patch data must be empty when advancing or closing an assignment.' );
        $this->get_status()->set_code( 400 );
      }
      else if( !is_null( $record->end_datetime ) )
      {
        $this->set_data( 'Cannot advance or close the assignment since it is already closed.' );
        $this->get_status()->set_code( 409 );
      }
      else
      {
        // check if the qnaire script is complete or not (used below)
        $db_interview = $record->get_interview();
        $db_participant = $db_interview->get_participant();
        $db_qnaire = $db_interview->get_qnaire();
        $old_sid = $tokens_class_name::get_sid();
        $tokens_class_name::set_sid( $db_qnaire->get_script()->sid );
        $tokens_mod = lib::create( 'database\modifier' );
        $tokens_class_name::where_token( $tokens_mod, $db_participant, false );
        $tokens_mod->where( 'completed', '!=', 'N' );
        $this->is_survey_complete = 0 < $tokens_class_name::count( $tokens_mod );

        $has_open_phone_call = $record->has_open_phone_call();
        if( 'advance' == $operation )
        {
          if( 0 == $has_open_phone_call )
          {
            $this->set_data( 'An assignment can only be advanced during an open call.' );
            $this->get_status()->set_code( 409 );
          }
          else if( !$this->is_survey_complete )
          {
            $this->set_data( 'The assignment cannot be advanced as the questionnaire is not complete.' );
            $this->get_status()->set_code( 409 );
          }
          else
          {
            // make sure there is another questionnaire after the current one
            $qnaire_mod = lib::create( 'database\modifier' );
            $qnaire_mod->where( 'rank', '=', $db_qnaire->rank + 1 );
            if( 0 == $qnaire_class_name::count( $qnaire_mod ) )
            {
              $this->set_data( 'There are no other questionnaires to advance to.' );
              $this->get_status()->set_code( 409 );
            }
          }
        }
        else if( 'close' == $operation )
        {
          if( 0 < $has_open_phone_call )
          {
            $this->set_data( 'An assignment cannot be closed during an open call.' );
            $this->get_status()->set_code( 409 );
          }
        }

        $tokens_class_name::set_sid( $old_sid );
      }
    }
    else if( 'POST' == $method )
    {
      // do not allow more than one open assignment
      if( $db_user->has_open_assignment() )
      {
        $this->set_data( 'Cannot create a new assignment since you already have one open.' );
        $this->get_status()->set_code( 409 );
      }
      else
      {
        // repopulate the participant immediately to make sure they are still available for an assignment
        $post_object = $this->get_file_as_object();
        $db_participant = lib::create( 'database\participant', $post_object->participant_id );
        $db_participant->repopulate_queue( false );
        $queue_mod = lib::create( 'database\modifier' );
        $queue_mod->where( 'queue.rank', '!=', NULL );
        if( 0 == $db_participant->get_queue_count( $queue_mod ) )
        {
          $this->set_data( 'The participant is no longer available for an interview.' );
          $this->get_status()->set_code( 409 );
        }
        else if( !is_null( $db_participant->get_current_assignment() ) )
        {
          $this->set_data(
            'Cannot create a new assignment since the participant is already assigned to a different user.' );
          $this->get_status()->set_code( 409 );
        }
      }
    }
  }

  /**
   * Extend parent method
   */
  public function prepare_read( $select, $modifier )
  {
    parent::prepare_read( $select, $modifier );

    // restrict by site
    $db_restrict_site = $this->get_restricted_site();
    if( !is_null( $db_restrict_site ) ) $modifier->where( 'assignment.site_id', '=', $db_restrict_site->id );

    if( $select->has_table_columns( 'queue' ) )
      $modifier->left_join( 'queue', 'assignment.queue_id', 'queue.id' );

    if( $select->has_table_columns( 'user' ) )
      $modifier->left_join( 'user', 'assignment.user_id', 'user.id' );

    if( $select->has_table_columns( 'site' ) )
      $modifier->left_join( 'site', 'assignment.site_id', 'site.id' );

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

    $now = util::get_datetime_object();
    $operation = $this->get_argument( 'operation', false );
    if( 'POST' == $this->get_method() && 'open' == $operation )
    {
      $session = lib::create( 'business\session' );
      $db_user = $session->get_user();
      $db_site = $session->get_site();

      // use the post object to fill in the record columns
      $post_object = $this->get_file_as_object();
      $db_participant = lib::create( 'database\participant', $post_object->participant_id );
      $db_interview = $db_participant->get_effective_interview();

      $record->user_id = $db_user->id;
      $record->site_id = $db_site->id;
      $record->interview_id = $db_interview->id;
      $record->queue_id = $db_participant->current_queue_id;
      $record->start_datetime = $now;
    }
    else if( 'PATCH' == $this->get_method() && ( 'advance' == $operation || 'close' == $operation ) )
    {
      // whether advancing or closing, the assignment is done
      $record->end_datetime = $now;

      if( 'advance' == $operation )
      {
        // end the phone call now
        $db_phone_call = $record->get_open_phone_call();
        $db_phone_call->end_datetime = $now;
        $db_phone_call->status = 'contacted';
        $db_phone_call->save();
        $db_phone_call->process_events();

        // make a note of which phone was called
        $this->current_phone_id = $db_phone_call->phone_id;
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
      $operation = $this->get_argument( 'operation', false );
      if( 'advance' == $operation )
      {
        $session = lib::create( 'business\session' );
        $now = util::get_datetime_object();
        $db_user = $session->get_user();
        $db_site = $session->get_site();

        // update any appointments or callbacks associated with this assignment
        $record->process_appointments_and_callbacks();
        $db_interview = $record->get_interview();
        $db_participant = $db_interview->get_participant();

        // mark the interview as complete and immediately update the queue
        $db_interview->complete();
        $db_participant->repopulate_queue( false );

        /*
        // get the next qnaire
        $qnaire_sel = lib::create( 'database\select' );
        $qnaire_sel->add_column( 'id' );
        $qnaire_sel->from( 'qnaire' );
        $qnaire_mod = lib::create( 'database\modifier' );
        $qnaire_mod->where( 'rank', '=', $db_interview->get_qnaire()->rank + 1 );
        $row = current( $qnaire_class_name->select( $qnaire_sel, $qnaire_mod ) );
        */

        // now create a new interview and assign it to the same user and start a new call
        // since the interview is now complete the effective interview will be newly created
        // by the participant record's get_effective_interview() method
        $db_next_interview = $db_participant->get_effective_interview();
        $db_next_interview->start_datetime = $now;
        $db_next_interview->save();

        $db_assignment = lib::create( 'database\assignment' );
        $db_assignment->user_id = $db_user->id;
        $db_assignment->site_id = $db_site->id;
        $db_assignment->interview_id = $db_next_interview->id;
        $db_assignment->queue_id = $record->queue_id;
        $db_assignment->start_datetime = $now;
        $db_assignment->save();

        $db_phone_call = lib::create( 'database\phone_call' );
        $db_phone_call->assignment_id = $db_assignment->id;
        $db_phone_call->phone_id = $this->current_phone_id;
        $db_phone_call->start_datetime = $now;
        $db_phone_call->save();
      }
      else if( 'close' == $operation )
      {
        // delete the assignment if there are no phone calls, or set appointment/callback reached if there are
        if( 0 == $record->get_phone_call_count() ) $record->delete();
        else
        {
          // update any appointments or callbacks associated with this assignment
          $record->process_appointments_and_callbacks();

          // mark the interview as complete if the survey is complete
          if( $this->is_survey_complete ) $record->get_interview()->complete();
        }
      }
    }
  }

  // TODO: document
  protected $is_survey_complete = NULL;

  // TODO: document
  protected $current_phone_id = NULL;
}
