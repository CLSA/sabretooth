<?php
/**
 * interview.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\database
 * @filesource
 */

namespace sabretooth\database;
use sabretooth\log, sabretooth\util;
use sabretooth\business as bus;
use sabretooth\exception as exc;

/**
 * interview: record
 *
 * @package sabretooth\database
 */
class interview extends has_note
{
  /**
   * Returns the time in seconds that it took to complete a particular phase of this interview
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param db\phase $db_phase Which phase of the interview to get the time of.
   * @param db\assignment $db_assignment Repeated phases have their times measured for each
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
    
    limesurvey\survey::set_sid( $db_phase->sid );
    $survey_mod = new modifier();
    $survey_mod->where( 'token', '=',
      limesurvey\tokens::determine_token_string( $this, $db_assignment ) );
    $survey_list = limesurvey\survey::select( $survey_mod );

    if( 0 == count( $survey_list ) ) return 0.0;

    if( 1 < count( $survey_list ) ) log::alert( sprintf(
      'There are %d surveys using the same token (%s)! for SID %d',
      count( $survey_list ),
      $token,
      $db_phase->sid ) );

    $db_survey = current( $survey_list );

    limesurvey\survey_timings::set_sid( $db_phase->sid );
    $timing_mod = new modifier();
    $timing_mod->where( 'id', '=', $db_survey->id );
    $db_timings = current( limesurvey\survey_timings::select( $timing_mod ) );
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
    $phase_mod = new modifier();
    $phase_mod->where( 'repeated', '!=', true );
    foreach( $this->get_qnaire()->get_phase_list( $phase_mod ) as $db_phase )
    {
      // update tokens
      limesurvey\tokens::set_sid( $db_phase->sid );
      $tokens_mod = new modifier();
      $tokens_mod->where( 'token', '=',
        limesurvey\tokens::determine_token_string( $this ) );
      $tokens_mod->where( 'completed', '=', 'N' );
      foreach( limesurvey\tokens::select( $tokens_mod ) as $db_tokens )
      {
        $db_tokens->completed = util::get_datetime_object()->format( 'Y-m-d H:i' );
        $db_tokens->usesleft = 0;
        $db_tokens->save();
      }

      // update surveys
      limesurvey\survey::set_sid( $db_phase->sid );
      $survey_mod = new modifier();
      $survey_mod->where( 'token', '=',
        limesurvey\tokens::determine_token_string( $this ) );
      $survey_mod->where( 'submitdate', '=', NULL );

      // get the last page for this survey
      $lastpage = limesurvey\survey::db()->get_one( sprintf(
        'SELECT MAX( lastpage ) FROM %s',
        limesurvey\survey::get_table_name() ) );

      foreach( limesurvey\survey::select( $survey_mod ) as $db_survey )
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
}
?>
