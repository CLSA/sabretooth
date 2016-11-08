<?php
/**
 * interview.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\database;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * interview: record
 */
class interview extends \cenozo\database\interview
{
  /**
   * Extends parent method
   */
  public function complete( $db_credit_site = NULL )
  {
    parent::complete( $db_credit_site );

    // record the script finished event
    $this->get_qnaire()->get_script()->add_finished_event_types( $this->get_participant() );
  }

  /**
   * Forces an interview to become completed.
   * 
   * This method will update an interview's status to be complete.  It will also update the
   * correspinding limesurvey data to be set as complete.  This action cannot be undone.
   * @author Patrick Emond <emondpd@mcmaster.ca>
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

    // finally, update the record
    $this->complete();

    $tokens_class_name::set_sid( $old_tokens_sid );
    $survey_class_name::set_sid( $old_survey_sid );
  }

  /**
   * Forces an interview to become incomplete.
   * 
   * This method will update an interview's status to be incomplete.  It will also delete the
   * correspinding limesurvey data.  This action cannot be undone.
   * @author Patrick Emond <emondpd@mcmaster.ca>
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
