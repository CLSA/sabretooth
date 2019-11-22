<?php
/**
 * interview.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */

namespace sabretooth\database;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * interview: record
 */
class interview extends \cenozo\database\interview
{
  /**
   * Determines whether the script associated with this interview has been completed
   * 
   * @return boolean
   * @access public
   */
  public function is_survey_complete()
  {
    $db_script = $this->get_qnaire()->get_script();

    if( 'pine' == $db_script->get_type() )
    {
      $cenozo_manager = lib::create( 'business\cenozo_manager', 'pine' );
      $response = $cenozo_manager->get( sprintf(
        'qnaire/%d/response/participant_id=%d?no_activity=1&select={"column":["submitted"]}',
        $db_script->pine_qnaire_id,
        $this->get_participant()->id
      ) );
      $is_complete = $response->submitted;
    }
    else
    {
      $tokens_class_name = lib::get_class_name( 'database\limesurvey\tokens' );
      $old_sid = $tokens_class_name::get_sid();
      $tokens_class_name::set_sid( $db_script->sid );
      $tokens_mod = lib::create( 'database\modifier' );
      $tokens_class_name::where_token( $tokens_mod, $this->get_participant(), false );
      $tokens_mod->where( 'completed', '!=', 'N' );
      $is_complete = 0 < $tokens_class_name::count( $tokens_mod );
      $tokens_class_name::set_sid( $old_sid );
    }

    return $is_complete;
  }

  /**
   * Extends parent method
   */
  public function complete( $db_credit_site = NULL )
  {
    $qnaire_class_name = lib::get_class_name( 'database\qnaire' );

    parent::complete( $db_credit_site );

    $db_participant = $this->get_participant();

    // record the script finished event
    $this->get_qnaire()->get_script()->add_finished_event_types( $db_participant );

    // If there are any appointments belonging to this interview which are unassigned then immediately
    // create make the next interview an reassign it (or delete it if this is the last interview)
    $appointment_mod = lib::create( 'database\modifier' );
    $appointment_mod->where( 'appointment.assignment_id', '=', NULL );
    $appointment_mod->where( 'appointment.outcome', '=', NULL );
    if( 0 < $this->get_appointment_count( clone $appointment_mod ) )
    {
      // see if there is a next qnaire
      $db_interview = NULL;
      $db_next_qnaire = $qnaire_class_name::get_unique_record( 'rank', $this->get_qnaire()->rank + 1 );
      if( !is_null( $db_next_qnaire ) )
      {
        $db_interview = new static();
        $db_interview->qnaire_id = $db_next_qnaire->id;
        $db_interview->participant_id = $db_participant->id;
        $db_interview->start_datetime = util::get_datetime_object();
        $db_interview->save();
      }

      foreach( $this->get_appointment_object_list( $appointment_mod ) as $db_appointment )
      {
        if( is_null( $db_next_qnaire ) ) $db_appointment->delete();
        else
        {
          $db_appointment->interview_id = $db_interview->id;
          $db_appointment->save();
        }
      }
    }
  }

  /**
   * Forces an interview to become completed.
   * 
   * This method will update an interview's status to be complete.  It will also update the
   * correspinding limesurvey data to be set as complete.  This action cannot be undone.
   * @access public
   */
  public function force_complete()
  {
    if( is_null( $this->id ) )
    {
      log::warning( 'Tried to force complete interview with no primary key.' );
      return;
    }

    // do nothing if the interview is already set as completed
    if( !is_null( $this->end_datetime ) ) return;

    $util_class_name = lib::get_class_name( 'util' );
    $tokens_class_name = lib::get_class_name( 'database\limesurvey\tokens' );
    $survey_class_name = lib::get_class_name( 'database\limesurvey\survey' );
    $db_participant = $this->get_participant();

    // update the token and survey associated with this interview
    $now = $util_class_name::get_datetime_object();
    $db_script = $this->get_qnaire()->get_script();

    if( 'pine' == $db_script->get_type() )
    {
      $cenozo_manager = lib::create( 'business\cenozo_manager', 'pine' );
      $response = $cenozo_manager->patch(
        sprintf( 'qnaire/%d/response/participant_id=%d', $db_script->pine_qnaire_id, $db_participant->id ),
        array( 'submitted' => true )
      );
    }
    else
    {
      $old_tokens_sid = $tokens_class_name::get_sid();
      $tokens_class_name::set_sid( $db_script->sid );
      $old_survey_sid = $survey_class_name::get_sid();
      $survey_class_name::set_sid( $db_script->sid );

      $tokens_mod = lib::create( 'database\modifier' );
      $tokens_class_name::where_token( $tokens_mod, $db_participant, false );
      $tokens_mod->where( 'completed', '=', 'N' );
      foreach( $tokens_class_name::select_objects( $tokens_mod ) as $db_tokens )
      {
        $db_tokens->completed = $now->format( 'Y-m-d' );
        $db_tokens->usesleft = 0;
        $db_tokens->save();
      }

      // get the last page for this survey
      $lastpage_sel = lib::create( 'database\select' );
      $lastpage_sel->add_column( 'MAX( lastpage )', 'lastpage', false );
      $lastpage_sel->from( $survey_class_name::get_table_name() );
      $lastpage = $survey_class_name::db()->get_one( $lastpage_sel->get_sql() );

      $survey_mod = lib::create( 'database\modifier' );
      $tokens_class_name::where_token( $survey_mod, $db_participant, false );
      $survey_mod->where( 'submitdate', '=', NULL );
      foreach( $survey_class_name::select_objects( $survey_mod ) as $db_survey )
      {
        $db_survey->submitdate = $now;
        if( $lastpage ) $db_survey->lastpage = $lastpage;
        $db_survey->save();
      }

      $tokens_class_name::set_sid( $old_tokens_sid );
      $survey_class_name::set_sid( $old_survey_sid );
    }

    // finally, update the record
    $this->complete();
  }

  /**
   * Forces an interview to become incomplete.
   * 
   * This method will update an interview's status to be incomplete.  It will also delete the
   * correspinding limesurvey data.  This action cannot be undone.
   * @access public
   */
  public function force_uncomplete()
  {
    if( is_null( $this->id ) )
    {
      log::warning( 'Tried to force uncomplete interview with no primary key.' );
      return;
    }

    // delete the token and survey associated with this interview
    $db_participant = $this->get_participant();
    $db_script = $this->get_qnaire()->get_script();
    $tokens_class_name = lib::get_class_name( 'database\limesurvey\tokens' );
    $survey_class_name = lib::get_class_name( 'database\limesurvey\survey' );

    $old_tokens_sid = $tokens_class_name::get_sid();
    $tokens_class_name::set_sid( $db_script->sid );
    $old_survey_sid = $survey_class_name::get_sid();
    $survey_class_name::set_sid( $db_script->sid );

    // delete tokens
    $tokens_mod = lib::create( 'database\modifier' );
    $tokens_class_name::where_token( $tokens_mod, $db_participant, false );
    foreach( $tokens_class_name::select_objects( $tokens_mod ) as $db_tokens )
    {
      $db_tokens->completed = 'N';
      $db_tokens->usesleft = 1;
      $db_tokens->save();
    }

    // delete surveys
    $survey_mod = lib::create( 'database\modifier' );
    $tokens_class_name::where_token( $survey_mod, $db_participant, false );
    foreach( $survey_class_name::select_objects( $survey_mod ) as $db_survey )
    {
      $db_survey->submitdate = NULL;
      $db_survey->save();
    }

    // finally, update the record
    $this->end_datetime = NULL;
    $this->site_id = NULL;
    $this->save();

    // remove finished events
    $db_event_type = $this->get_qnaire()->get_script()->get_finished_event_type();
    $event_mod = lib::create( 'database\modifier' );
    $event_mod->where( 'event_type_id', '=', $db_event_type->id );
    foreach( $db_participant->get_event_object_list( $event_mod ) as $db_event )
      $db_event->delete();

    $tokens_class_name::set_sid( $old_tokens_sid );
    $survey_class_name::set_sid( $old_survey_sid );
  }
}
