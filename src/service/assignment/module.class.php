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
class module extends \cenozo\service\assignment\module
{
  /**
   * Extend parent method
   */
  public function validate()
  {
    parent::validate();

    if( 300 > $this->get_status()->get_code() )
    {
      $qnaire_class_name = lib::get_class_name( 'database\qnaire' );
      $participant_class_name = lib::get_class_name( 'database\participant' );
      $queue_class_name = lib::get_class_name( 'database\queue' );

      $method = $this->get_method();
      $operation = $this->get_argument( 'operation', false );

      if( 'PATCH' == $method &&
          ( 'advance' == $operation || 'close' == $operation || 'force_close' == $operation ) )
      {
        $record = $this->get_resource();

        // check if the qnaire script is complete or not (used below and in the parent class)
        $db_interview = $record->get_interview();
        $this->is_survey_complete = $db_interview->is_survey_complete();
        $this->db_participant = $db_interview->get_participant();
        $db_qnaire = $db_interview->get_qnaire();

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
      }
      else if( 'POST' == $method )
      {
        // repopulate the participant immediately to make sure they are still available for an assignment
        $post_object = $this->get_file_as_object();
        if( is_object( $post_object ) && property_exists( $post_object, 'participant_id' ) )
        {
          $this->db_participant->repopulate_queue( false );
          if( !is_null( $this->db_participant->get_current_assignment() ) )
          {
            $this->set_data(
              'Cannot create a new assignment since the participant is already assigned to a different user.' );
            $this->get_status()->set_code( 409 );
          }
          else if( 'open' == $operation )
          {
            $queue_mod = lib::create( 'database\modifier' );
            $queue_mod->where( 'queue.rank', '!=', NULL );
            if( 0 == $this->db_participant->get_queue_count( $queue_mod ) )
            {
              $this->set_data( 'The participant is no longer available for an interview.' );
              $this->get_status()->set_code( 409 );
            }
          }
        }
        else
        {
          $session = lib::create( 'business\session' );
          $db_user = $session->get_user();
          $db_site = $session->get_site();

          // get the highest ranking participant in the queue after repopulating if it is out of date
          $interval = $queue_class_name::get_interval_since_last_repopulate();
          if( is_null( $interval ) || 0 < $interval->days || 22 < $interval->h )
          { // it's been at least 23 hours since the non time-based queues have been repopulated
            $queue_class_name::repopulate();
            $queue_class_name::repopulate_time();
          }
          else
          {
            $interval = $queue_class_name::get_interval_since_last_repopulate_time();
            if( is_null( $interval ) || 0 < $interval->days || 0 < $interval->h || 0 < $interval->i )
            { // it's been at least one minute since the time-based queues have been repopulated
              $queue_class_name::repopulate_time();
            }
          }

          $participant_mod = lib::create( 'database\modifier' );
          $participant_mod->join(
            'queue_has_participant', 'participant.id', 'queue_has_participant.participant_id' );
          $participant_mod->join( 'queue', 'queue_has_participant.queue_id', 'queue.id' );
          $participant_mod->join( 'qnaire', 'queue_has_participant.qnaire_id', 'qnaire.id' );
          $participant_mod->where( 'queue.rank', '!=', NULL );
          $participant_mod->where( 'queue_has_participant.site_id', '=', $db_site->id );

          // restrict by user language
          $language_sel = lib::create( 'database\select' );
          $language_sel->add_column( 'id' );
          $user_language_list = array();
          foreach( $db_user->get_language_list( $language_sel ) as $language )
            $user_language_list[] = $language['id'];
          if( 0 < count( $user_language_list ) )
            $participant_mod->where( 'participant.language_id', 'IN', $user_language_list );
          $participant_mod->order( 'queue.rank' );
          $participant_mod->order( 'qnaire.rank' );

          $participant_sel = lib::create( 'database\select' );
          $participant_sel->from( 'participant' );
          $participant_sel->add_column( 'id' );

          $rows = $participant_class_name::select( $participant_sel, $participant_mod );
          if( 0 == count( $rows ) )
          {
            $this->set_data( 'There are no participants available for an assignment at this time, '.
                             'please try again later.' );
            $this->get_status()->set_code( 408 );
          }
          else
          {
            $this->db_participant = lib::create( 'database\participant', current( $rows )['id'] );
          }
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

    if( $select->has_table_columns( 'queue' ) )
      $modifier->left_join( 'queue', 'assignment.queue_id', 'queue.id' );

    if( $select->has_table_columns( 'qnaire' ) || $select->has_table_columns( 'script' ) )
    {
      if( !$modifier->has_join( 'interview' ) )
        $modifier->join( 'interview', 'assignment.interview_id', 'interview.id' );
      $modifier->join( 'qnaire', 'interview.qnaire_id', 'qnaire.id' );
      $modifier->join( 'script', 'script.id', 'qnaire.script_id' );
    }
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
      $record->queue_id = $this->db_participant->current_queue_id;
    }
    else if( 'PATCH' == $this->get_method() && 'advance' == $operation )
    {
      // end the phone call now
      $db_phone_call = $record->get_open_phone_call();
      $db_phone_call->end_datetime = $now;
      $db_phone_call->status = 'contacted';
      $db_phone_call->save();
      $db_phone_call->process_events();

      // make a note of which phone was called
      $this->current_phone_id = $db_phone_call->phone_id;

      $record->end_datetime = $now;
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
      $now = util::get_datetime_object();
      $operation = $this->get_argument( 'operation', false );
      if( 'advance' == $operation )
      {
        $session = lib::create( 'business\session' );

        // update any appointments or callbacks
        $record->post_process( true );
        $db_interview = $record->get_interview();
        $this->db_participant = $db_interview->get_participant();

        // mark the interview as complete and immediately update the queue
        $db_interview->complete();
        $this->db_participant->repopulate_queue( false );

        // now create a new interview and assign it to the same user and start a new call
        // since the interview is now complete the effective interview will be newly created
        // by the participant record's get_effective_interview() method
        $db_next_interview = $this->db_participant->get_effective_interview();
        $db_next_interview->start_datetime = $now;
        $db_next_interview->save();

        $db_assignment = lib::create( 'database\assignment' );
        $db_assignment->user_id = $session->get_user()->id;
        $db_assignment->role_id = $session->get_role()->id;
        $db_assignment->site_id = $session->get_site()->id;
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
    }
  }
}
