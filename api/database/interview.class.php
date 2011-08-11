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
    
    // create a new assignment to determine the phase from if none is provided
    if( is_null( $db_assignment ) )
    {
      $db_assignment = new assignment();
      $db_assignment->interview_id = $this->id;
    }
    $token = limesurvey\tokens::determine_token_string(
               $this,
               $db_phase->repeated ? $db_assignment : NULL );
    $survey_db = bus\session::self()->get_survey_database();
    return (float) $survey_db->get_one(
      sprintf( ' SELECT timing.interviewTime'.
               ' FROM survey_%s_timings AS timing, survey_%s AS survey'.
               ' WHERE timing.id = survey.id'.
               ' AND survey.token = %s',
               $db_phase->sid,
               $db_phase->sid,
               database::format_string( $token ) ) );
  }
}
?>
