<?php
/**
 * participant_report.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui\pull;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * Mailout required report data.
 * 
 * @abstract
 */
class participant_report extends \cenozo\ui\pull\participant_report
{
  /**
   * Processes arguments, preparing them for the operation.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access protected
   */
  protected function prepare()
  {
    parent::prepare();

    // get the report arguments
    $last_call_result = $this->get_argument( 'last_call_result' );

    if( 'any' != $last_call_result )
    {
      $this->sql_tables .=
        'JOIN participant_last_interview '.
        'ON participant.id = participant_last_interview.participant_id '.
        'JOIN interview_last_assignment '.
        'ON participant_last_interview.interview_id = interview_last_assignment.interview_id '.
        'JOIN assignment_last_phone_call '.
        'ON assignment_last_phone_call.assignment_id = interview_last_assignment.assignment_id '.
        'JOIN phone_call '.
        'ON interview_last_assignment.assignment_id = phone_call.assignment_id ';

      $this->modifier->where(
        'phone_call.status', '=', $last_call_result );
    }
  }
}
