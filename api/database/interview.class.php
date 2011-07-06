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
   * @return float
   * @access public
   */
  public function get_interview_time( $db_phase )
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

    $token = $this->get_assignment()->get_token( $db_phase );
    $survey_db = bus\session::self()->get_survey_database();
    return (float) $survey_db->get_one(
      sprintf( ' SELECT timing.interviewTime'.
               ' FROM survey_%s_timings AS timing, survey_%s AS survey'.
               ' WHERE timing.id = survey.id'.
               ' AND survey.token = %s',
               $db_phase->sid,
               $db_phase->sid,
               database::format_string( $this->get_assignment()->get_token( $db_phase ) ) ) );
  }
}
?>
