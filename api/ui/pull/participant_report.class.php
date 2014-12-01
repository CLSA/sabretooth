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

    $database_class_name = lib::get_class_name( 'database\database' );

    // get the report arguments
    $last_call_result = $this->get_argument( 'last_call_result' );
    $qnaire_id = $this->get_argument( 'qnaire_id' );
    $start_date = $this->get_argument( 'qnaire_start_date' );
    $end_date = $this->get_argument( 'qnaire_end_date' );

    if( 'any' != $last_call_result )
    {
      $this->modifier->join( 'participant_last_interview',
        'participant.id', 'participant_last_interview.participant_id' );
      $this->modifier->join( 'interview_last_assignment',
        'participant_last_interview.interview_id', 'interview_last_assignment.interview_id' );
      $this->modifier->join( 'assignment_last_phone_call',
        'assignment_last_phone_call.assignment_id', 'interview_last_assignment.assignment_id' );
      $this->modifier->join( 'phone_call',
        'interview_last_assignment.assignment_id', 'phone_call.assignment_id' );
      $this->modifier->where(
        'phone_call.status', '=', $last_call_result );
    }

    if( $qnaire_id || $start_date || $end_date )
    {
      $join_mod = lib::create( 'database\modifier' );
      $join_mod->where( 'participant.id', '=', 'queue_has_participant.participant_id', false );
      $join_mod->where( 'queue_has_participant.start_qnaire_date', '!=', NULL );
      if( $qnaire_id ) $join_mod->where( 'queue_has_participant.qnaire_id', '=', $qnaire_id );
      $this->modifier->join( 'queue_has_participant', $join_mod );

      $join_mod = lib::create( 'database\modifier' );
      $join_mod->where( 'queue_has_participant.queue_id', '=', 'queue.id', false );
      $join_mod->where( 'queue.name', '=', 'qnaire' );
      $this->modifier->join( 'queue', $join_mod );

      if( $start_date )
        $this->modifier->where( 'queue_has_participant.start_qnaire_date', '>=', $start_date );
      if( $end_date )
        $this->modifier->where( 'queue_has_participant.start_qnaire_date', '<=', $end_date );
    }
  }
}
