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
class interview extends \cenozo\database\has_note
{
  /**
   * Returns the time in seconds that it took to complete a particular phase of this interview
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param phase $db_phase Which phase of the interview to get the time of.
   * @param assignment $db_assignment Repeated phases have their times measured for each
   *                      iteration of the phase.  For repeated phases this determines which
   *                      assignment's time to return.  It is ignored for phases which are not
   *                      repeated.
   * @return float
   * @access public
   */
  public function get_interview_time( $db_phase, $db_assignment = NULL )
  {
    if( is_null( $this->id ) )
    {
      log::warning( 'Tried to determine interview time for interview with no id.' );
      return 0.0;
    }
    
    if( is_null( $db_phase ) )
    {
      log::warning( 'Tried to determine interview time for null phase.' );
      return 0.0;
    }

    if( $db_phase->repeated && is_null( $db_assignment ) )
    {
      log::warning(
        'Tried to determine interview time for repeating phase without an assignment.' );
      return 0.0;
    }
   
    $tokens_class_name = lib::get_class_name( 'database\limesurvey\tokens' );
    $tokens_class_name::set_sid( $db_phase->sid );
    $survey_mod = lib::create( 'database\modifier' );
    $survey_mod->where( 'token', '=',
      $tokens_class_name::determine_token_string( $this, $db_assignment ) );

    $survey_class_name = lib::get_class_name( 'database\limesurvey\survey' );
    $survey_class_name::set_sid( $db_phase->sid );
    $survey_list = $survey_class_name::select( $survey_mod );
    if( 0 == count( $survey_list ) ) return 0.0;

    if( 1 < count( $survey_list ) ) log::alert( sprintf(
      'There are %d surveys using the same token (%s)! for SID %d',
      count( $survey_list ),
      $token,
      $db_phase->sid ) );

    $db_survey = current( $survey_list );

    $timings_class_name = lib::get_class_name( 'database\limesurvey\survey_timings' );
    $timings_class_name::set_sid( $db_phase->sid );
    $timing_mod = lib::create( 'database\modifier' );
    $timing_mod->where( 'id', '=', $db_survey->id );
    $db_timings = current( $timings_class_name::select( $timing_mod ) );
    return $db_timings ? (float) $db_timings->interviewtime : 0.0;
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
    $phase_mod = lib::create( 'database\modifier' );
    $phase_mod->where( 'repeated', '!=', true );
    $tokens_class_name = lib::get_class_name( 'database\limesurvey\tokens' );
    $survey_class_name = lib::get_class_name( 'database\limesurvey\survey' );
    foreach( $this->get_qnaire()->get_phase_list( $phase_mod ) as $db_phase )
    {
      // update tokens
      $tokens_class_name::set_sid( $db_phase->sid );
      $tokens_mod = lib::create( 'database\modifier' );
      $tokens_mod->where( 'token', '=',
        $tokens_class_name::determine_token_string( $this ) );
      $tokens_mod->where( 'completed', '=', 'N' );
      foreach( $tokens_class_name::select( $tokens_mod ) as $db_tokens )
      {
        $db_tokens->completed = util::get_datetime_object()->format( 'Y-m-d H:i' );
        $db_tokens->usesleft = 0;
        $db_tokens->save();
      }

      // update surveys
      $survey_class_name::set_sid( $db_phase->sid );
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
        $db_survey->submitdate = util::get_datetime_object()->format( 'Y-m-d H:i:s' );
        if( $lastpage ) $db_survey->lastpage = $lastpage;
        $db_survey->save();
      }
    }

    // finally, update the record
    $this->completed = true;
    $this->save();
  }

  /**
   * Overrides the parent method in order to synchronize the recordings on file with those in
   * the database.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the selection.
   * @param boolean $count If true the total number of records instead of a list
   * @return array( record ) | int
   * @access public
   */
  public function get_recording_list( $modifier = NULL )
  {
    $this->update_recording_list();
    return parent::get_recording_list( $modifier );
  }

  /**
   * Overrides the parent method in order to synchronize the recordings on file with those in
   * the database.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the selection.
   * @param boolean $count If true the total number of records instead of a list
   * @return array( record ) | int
   * @access public
   */
  public function get_recording_count( $modifier = NULL )
  {
    $this->update_recording_list();
    return parent::get_recording_count( $modifier );
  }

  /**
   * Returns the participant's response for consenting to the cognitive section of the interview
   * TODO: this method is qnaire-specific, future development needs to make this more generic
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return string
   * @access public
   */
   public function get_cognitive_consent()
   {
     $survey_class_name = lib::get_class_name( 'database\limesurvey\survey' );
     $tokens_class_name = lib::get_class_name( 'database\limesurvey\tokens' );

     $survey_class_name::set_sid( 27345 ); // TODO: make dynamic
     $survey_mod = lib::create( 'database\modifier' );
     $survey_mod->where( 'token', '=', $tokens_class_name::determine_token_string( $this ) );
     $survey_list = $survey_class_name::select( $survey_mod );
     $db_survey = current( $survey_list );  
     return $db_survey ? $db_survey->get_response( 'COG_REC_TRM' ) : 'NULL';
   }

  /**
   * Builds the recording list based on recording files found in the monitor path (if set)
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access protected
   */
  protected function update_recording_list()
  {
    // make sure that all recordings on disk have a corresponding database record
    if( is_dir( VOIP_MONITOR_PATH ) )
    {
      $values = '';
      $first = true;
      foreach( glob( sprintf( '%s/%d_*-out.wav', VOIP_MONITOR_PATH, $this->id ) ) as $filename )
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
            $rank = 4 <= count( $parts ) ? $parts[2] + 1 : 1;
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
          'VALUES %s', $values ) );
      }
    }
  }

  /**
   * Returns the most recent total number of consecutive failed calls.  A maximum of one
   * failed call per assignment is counted.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return int
   * @access public
   */
  public function get_failed_call_count()
  {
    if( is_null( $this->id ) )
    {
      log::warning( 'Tried to get failed call count for interview with no id.' );
      return;
    }
    
    $assignment_mod = lib::create( 'database\modifier' );
    $assignment_mod->order_desc( 'start_datetime' );
    $assignment_mod->where( 'end_datetime', '!=', NULL );
    $failed_calls = 0;
    foreach( $this->get_assignment_list( $assignment_mod ) as $db_assignment )
    {
      // find the most recently completed phone call
      $phone_call_mod = lib::create( 'database\modifier' );
      $phone_call_mod->order_desc( 'start_datetime' );
      $phone_call_mod->where( 'status', '=', 'contacted' );
      $phone_call_mod->where( 'end_datetime', '!=', NULL );
      if( 0 < $db_assignment->get_phone_call_count( $phone_call_mod ) ) break;
      $failed_calls++;
    }

    return $failed_calls;
  }
  
  /**
   * Creates the interview_failed_call_count temporary table needed by all queues.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   * @static
   */
  public static function create_interview_failed_call_count()
  {
    if( static::$interview_failed_call_count_created ) return;
    static::db()->execute( 'SET @next := @series := @nc := @interview_id := 0' );
    $sql = 'CREATE TEMPORARY TABLE IF NOT EXISTS interview_failed_call_count '.
           static::$interview_failed_call_count_sql;
    static::db()->execute( $sql );
    static::$interview_failed_call_count_created = true;
  }
  
  /**
   * Whether the interview_failed_call_count temporary table has been created.
   * @var boolean
   * @static
   */
  protected static $interview_failed_call_count_created = false;

  /**
   * A string containing the SQL used to create the interview_failed_call_count data
   * @var string
   * @static
   */
  protected static $interview_failed_call_count_sql = <<<'SQL'
SELECT interview_id, total FROM
(
  SELECT interview_id, series, max( nc ) AS total
  FROM
  (
    SELECT
      @next := IF( interview_id != COALESCE( @interview_id, 0 ) OR status = "contacted", 1, 0 ) AS next,
      @series := COALESCE( @series, 0 ) + IF( @next, 1, 0 ) AS series,
      @nc := IF( @next, IF( status = "contacted", 0, 1 ), @nc + 1 ) AS nc,
      @interview_id := interview_id AS interview_id,
      status
    FROM
    (
      SELECT interview_id, status
      FROM assignment
      JOIN phone_call on assignment.id = phone_call.assignment_id
      WHERE phone_call.end_datetime is not null
      ORDER by interview_id, phone_call.end_datetime
    ) AS t1
  ) AS t2
  GROUP BY interview_id, series ORDER BY interview_id, series DESC
) AS t3
GROUP BY interview_id
SQL;
}

// define the join to the participant_site table
$participant_site_mod = lib::create( 'database\modifier' );
$participant_site_mod->where(
  'interview.participant_id', '=', 'participant_site.participant_id', false );
interview::customize_join( 'participant_site', $participant_site_mod );

// define the join to the last assignment
$assignment_mod = lib::create( 'database\modifier' );
$assignment_mod->where( 'interview.id', '=', 'interview_last_assignment.interview_id', false );
$assignment_mod->where( 'interview_last_assignment.assignment_id', '=', 'assignment.id', false );
interview::customize_join( 'assignment', $assignment_mod );
?>
