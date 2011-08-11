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
    return $db_timings ? (float) $db_timings->interviewTime : 0.0;
  }
}
?>
