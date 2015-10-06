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
class interview extends \cenozo\database\record
{
  /**
   * Overrides the parent save method.
   */
  public function save()
  {
    parent::save();
    $this->get_participant()->update_queue_status();
  }

  /**
   * Get the interview's last (most recent) assignment.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return assignment
   * @access public
   */
  public function get_last_assignment()
  {
    // check the last key value
    if( is_null( $this->id ) )
    {
      log::warning( 'Tried to query interview with no id.' );
      return NULL;
    }
    
    $select = lib::create( 'database\select' );
    $select->from( 'interview_last_assignment' );
    $select->add_column( 'assignment_id' );
    $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'interview_id', '=', $this->id );

    $assignment_id = static::db()->get_one( sprintf( '%s %s', $select->get_sql(), $modifier->get_sql() ) );
    return $assignment_id ? lib::create( 'database\assignment', $assignment_id ) : NULL;
  }

  /**
   * Performes all necessary steps when completing an interview.
   * 
   * This method encapsulates all processing required when an interview is completed.
   * If you wish to "force" the completion or uncompletion of an interview please use
   * the force_complete() and force_uncomplete() methods intead.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function complete()
  {
    // update the record
    $this->completed = true;
    $this->save();

    // record the event (if one exists)
    $db_event_type = $this->get_qnaire()->get_script()->get_event_type();
    if( !is_null( $db_event_type ) )
    {
      // make sure the event doesn't already exist
      $event_mod = lib::create( 'database\modifier' );
      $event_mod->where( 'event_type_id', '=', $db_event_type->id );
      if( 0 == $this->get_participant()->get_event_count( $event_mod ) )
      {
        $db_event = lib::create( 'database\event' );
        $db_event->participant_id = $this->participant_id;
        $db_event->event_type_id = $db_event_type->id;
        $db_event->datetime = util::get_datetime_object();
        $db_event->save();
      }
    }
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
      log::warning( 'Tried to force complete interview with no id.' );
      return;
    }
    
    // do nothing if the interview is already set as completed
    if( $this->completed ) return;
    
    // update the token and survey associated with this interview
    $now = util::get_datetime_object()->format( 'Y-m-d' );
    $token = $this->get_participant()->uid;
    $db_script = $this->get_qnaire()->get_script();
    $tokens_class_name = lib::get_class_name( 'database\limesurvey\tokens' );
    $tokens_class_name::set_sid( $db_script->sid );
    $survey_class_name = lib::get_class_name( 'database\limesurvey\survey' );
    $survey_class_name::set_sid( $db_script->sid );
    
    $tokens_mod = lib::create( 'database\modifier' );
    $tokens_mod->where( 'token', '=', $token );
    $tokens_mod->where( 'completed', '=', 'N' );
    foreach( $tokens_class_name::select( $tokens_mod ) as $db_tokens )
    {
      $db_tokens->completed = $now;
      $db_tokens->usesleft = 0;
      $db_tokens->save();
    }

    // get the last page for this survey
    $lastpage_sel = lib::create( 'database\select' );
    $lastpage_sel->add_column( 'MAX( lastpage )', 'lastpage', false );
    $lastpage_sel->from( $survey_class_name::get_table_name() );
    $lastpage = $survey_class_name::db()->get_one( $lastpage_sel->get_sql() );

    $survey_mod = lib::create( 'database\modifier' );
    $survey_mod->where( 'token', '=', $token );
    $survey_mod->where( 'submitdate', '=', NULL );
    foreach( $survey_class_name::select( $survey_mod ) as $db_survey )
    {
      $db_survey->submitdate = $now;
      if( $lastpage ) $db_survey->lastpage = $lastpage;
      $db_survey->save();
    }

    // finally, update the record
    $this->complete();
  }

  /**
   * Forces an interview to become completed.
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
      log::warning( 'Tried to force uncomplete interview with no id.' );
      return;
    }
    
    // delete the token and survey associated with this interview
    $db_participant = $this->get_participant();
    $token = $db_participant->uid;
    $db_script = $this->get_qnaire()->get_script();
    $tokens_class_name = lib::get_class_name( 'database\limesurvey\tokens' );
    $tokens_class_name::set_sid( $db_script->sid );
    $survey_class_name = lib::get_class_name( 'database\limesurvey\survey' );
    $survey_class_name::set_sid( $db_script->sid );

    // delete tokens
    $tokens_mod = lib::create( 'database\modifier' );
    $tokens_mod->where( 'token', '=', $token );
    foreach( $tokens_class_name::select( $tokens_mod ) as $db_tokens )
    {
      $db_tokens->completed = 'N';
      $db_tokens->usesleft = 1;
      $db_tokens->save();
    }

    // delete surveys
    $survey_mod = lib::create( 'database\modifier' );
    $survey_mod->where( 'token', '=', $token );
    foreach( $survey_class_name::select( $survey_mod ) as $db_survey )
    {
      $db_survey->submitdate = NULL;
      $db_survey->save();
    }

    // finally, update the record
    $this->completed = false;
    $this->save();

    // remove completed events (if any exist)
    $db_event_type = $this->get_qnaire()->get_script()->get_event_type();
    if( !is_null( $db_event_type ) )
    {
      $event_mod = lib::create( 'database\modifier' );
      $event_mod->where( 'event_type_id', '=', $db_event_type->id );
      foreach( $db_participant->get_event_object_list( $event_mod ) as $db_event )
        $db_event->delete();
    }
  }
}
