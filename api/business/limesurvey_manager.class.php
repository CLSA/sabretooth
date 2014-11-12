<?php
/**
 * limesurvey_manager.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\business;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * Manages communication with Limesurvey
 */
class limesurvey_manager extends \cenozo\singleton
{
  /**
   * Constructor.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access protected
   */
  protected function __construct()
  {
    // nothing required
  }

  /**
   * Get a participant's value for a particular variable
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\interview $db_interview The interview to get the value from
   * @param database\phase $db_phase The phase of a qnaire to get the value from
   * @param string $question The title of the limesurvey question
   * @param boolean $last Whether to return the last response instead of first
   *                      (for repeating phases only)
   * @param boolean $notnull Whether to consider NULL answers when returning a response
   *                         (for repeating phases only)
   * @return string (may be an array of strings if the phase is repeatable)
   * @throws exception\argument
   * @access public
   */
  public function get_value( $db_interview, $db_phase, $question, $last = false, $notnull = false )
  {
    $value = NULL;
    $source_survey_class_name = lib::get_class_name( 'database\source_survey' );
    $survey_class_name = lib::get_class_name( 'database\limesurvey\survey' );

    if( is_null( $db_interview ) ||
        false === strpos( get_class( $db_interview ), 'database\interview' ) )
      throw lib::create( 'exception\argument', 'db_interview', $db_interview, __METHOD__ );
    if( is_null( $db_phase ) ||
        false === strpos( get_class( $db_phase ), 'database\phase' ) )
      throw lib::create( 'exception\argument', 'db_phase', $db_phase, __METHOD__ );
    if( 0 == strlen( $question ) )
      throw lib::create( 'exception\argument', 'question', $question, __METHOD__ );

    $db_source_survey = $source_survey_class_name::get_unique_record(
      array( 'phase_id', 'source_id' ),
      array( $db_phase->id, $db_interview->get_participant()->source_id ) );
    $survey_class_name::set_sid(
      is_null( $db_source_survey ) ? $db_phase->sid : $db_source_survey->sid );

    $found = false;
    foreach( $survey_class_name::get_responses( $db_interview->id.'_%', $question ) as $response )
    {
      // if $notnull is true then make sure the value isn't null
      if( !$notnull || !is_null( $response ) )
      {
        $value = $response;
        $found = true;
      }

      if( !$last && $found ) break;
    }

    return $value;
  }
}
