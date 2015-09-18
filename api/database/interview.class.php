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

    // update the recording list
    $this->update_recording_list();

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
    
    // update all uncomplete tokens and surveys associated with this interview which are
    // associated with phases which are not repeated (tokens should not include assignments)
    $phase_sel = lib::create( 'database\select' );
    $phase_sel->add_column( 'sid' );
    $phase_mod = lib::create( 'database\modifier' );
    $phase_mod->where( 'repeated', '!=', true );
    $tokens_class_name = lib::get_class_name( 'database\limesurvey\tokens' );
    $survey_class_name = lib::get_class_name( 'database\limesurvey\survey' );
    foreach( $this->get_qnaire()->get_script()->get_phase_list( $phase_sel, $phase_mod ) as $phase )
    {
      $now_datetime_obj = util::get_datetime_object();
      // update tokens
      $tokens_class_name::set_sid( $phase['sid'] );
      $tokens_mod = lib::create( 'database\modifier' );
      $tokens_mod->where( 'token', '=',
        $tokens_class_name::determine_token_string( $this ) );
      $tokens_mod->where( 'completed', '=', 'N' );
      foreach( $tokens_class_name::select( $tokens_mod ) as $db_tokens )
      {
        $db_tokens->completed = $now_datetime_obj->format( 'Y-m-d H:i' );
        $db_tokens->usesleft = 0;
        $db_tokens->save();
      }

      // update surveys
      $survey_class_name::set_sid( $phase['sid'] );
      $survey_mod = lib::create( 'database\modifier' );
      $survey_mod->where( 'token', '=',
        $tokens_class_name::determine_token_string( $this ) );
      $survey_mod->where( 'submitdate', '=', NULL );

      // get the last page for this survey
      $lastpage = $survey_class_name::db()->get_one( sprintf(
        'SELECT MAX( lastpage ) FROM %s',
        $survey_class_name::get_table_name() ) );

      foreach( $survey_class_name::select( $survey_mod ) as $db_survey )
      {
        $db_survey->submitdate = $now_datetime_obj->format( 'Y-m-d H:i:s' );
        if( $lastpage ) $db_survey->lastpage = $lastpage;
        $db_survey->save();
      }
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
    
    // delete all tokens and surveys associated with this interview which are
    // associated with phases which are not repeated (tokens should not include assignments)
    $phase_sel = lib::create( 'database\select' );
    $phase_sel->add_column( 'sid' );
    $phase_mod = lib::create( 'database\modifier' );
    $phase_mod->where( 'repeated', '!=', true );
    $tokens_class_name = lib::get_class_name( 'database\limesurvey\tokens' );
    $survey_class_name = lib::get_class_name( 'database\limesurvey\survey' );
    foreach( $this->get_qnaire()->get_script()->get_phase_list( $phase_sel, $phase_mod ) as $phase )
    {
      // delete tokens
      $tokens_class_name::set_sid( $phase['sid'] );
      $tokens_mod = lib::create( 'database\modifier' );
      $tokens_mod->where( 'token', '=',
        $tokens_class_name::determine_token_string( $this ) );
      foreach( $tokens_class_name::select( $tokens_mod ) as $db_tokens )
      {
        $db_tokens->completed = 'N';
        $db_tokens->usesleft = 1;
        $db_tokens->save();
      }

      // delete surveys
      $survey_class_name::set_sid( $phase['sid'] );
      $survey_mod = lib::create( 'database\modifier' );
      $survey_mod->where( 'token', '=',
        $tokens_class_name::determine_token_string( $this ) );

      foreach( $survey_class_name::select( $survey_mod ) as $db_survey )
      {
        $db_survey->submitdate = NULL;
        $db_survey->save();
      }
    }

    // finally, update the record
    $this->completed = false;
    $this->save();
    $this->update_recording_list();

    // remove completed events (if any exist)
    $db_event_type = $this->get_qnaire()->get_script()->get_event_type();
    if( !is_null( $db_event_type ) )
    {
      $event_mod = lib::create( 'database\modifier' );
      $event_mod->where( 'event_type_id', '=', $db_event_type->id );
      foreach( $this->get_participant()->get_event_object_list( $event_mod ) as $db_event )
        $db_event->delete();
    }
  }

  /**
   * Builds the recording list based on recording files found in the monitor path (if set)
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access protected
   */
  public function update_recording_list()
  {
    // make sure that all recordings on disk have a corresponding database record
    if( is_dir( VOIP_MONITOR_PATH ) )
    {
      // create new recording record based on this interview
      $db_recording = lib::create( 'database\recording' );
      $db_recording->interview_id = $this->id;
      $glob_search = sprintf( '%s/%s',
                              VOIP_MONITOR_PATH,
                              str_replace( '_0-01', '_*', $db_recording->get_filename() ) );

      $values = '';
      $first = true;
      foreach( glob( $glob_search ) as $filename )
      {
        // remove the path from the filename
        $parts = preg_split( '#/#', $filename );
        if( 2 <= count( $parts ) )
        {
          // get the interview and assignment id from the filename
          $parts = preg_split( '/[-_]/', end( $parts ) );
          if( 3 <= count( $parts ) )
          {
            $assignment_id = 0 < $parts[1] ? $parts[1] : 'NULL';
            $rank = intval( $parts[2] );
            $values .= sprintf( '%s( %d, %s, %d )',
                                $first ? '' : ', ',
                                $this->id,
                                $assignment_id,
                                $rank );
            $first = false;
          }
        }
      }

      if( !$first )
      {
        static::db()->execute( sprintf(
          'INSERT IGNORE INTO recording ( interview_id, assignment_id, rank ) '.
          "\n".'VALUES %s', $values ) );
      }
    }
  }
}
