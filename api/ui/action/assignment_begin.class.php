<?php
/**
 * assignment_begin.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui\action;
use sabretooth\log, sabretooth\util;
use sabretooth\business as bus;
use sabretooth\database as db;
use sabretooth\exception as exc;

/**
 * action assignment begin
 *
 * Assigns a participant to an assignment.
 * @package sabretooth\ui
 */
class assignment_begin extends \sabretooth\ui\action
{
  /**
   * Constructor.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args Action arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'assignment', 'begin', $args );
  }

  /**
   * Executes the action.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function finish()
  {
    $session = bus\session::self();

    // search through every queue for a new assignment until one is found
    $modifier = new db\modifier();
    $modifier->where( 'rank', '!=', NULL );
    $modifier->order( 'rank' );
    $db_origin_queue = NULL;
    $db_participant = NULL;
    $db_appointment_id = NULL;
    foreach( db\queue::select( $modifier ) as $db_queue )
    {
      $mod = new db\modifier();
      $mod->limit( 1 );
      $db_queue->set_site( $session->get_site() );
      $participant_list = $db_queue->get_participant_list( $mod );
      if( 1 == count( $participant_list ) )
      {
        $db_origin_queue = $db_queue;
        $db_participant = current( $participant_list );

        break;
      }
    }

    if( is_null( $db_participant ) )
      throw new exc\notice(
        'There are no participants currently available.', __METHOD__ );
    
    // make sure the qnaire has phases
    $db_qnaire = new db\qnaire( $db_participant->current_qnaire_id );
    if( 0 == $db_qnaire->get_phase_count() )
      throw new exc\notice(
        'This participant\'s next questionnaire is not yet ready.  '.
        'Please immediately report this problem to a supervisor.',
        __METHOD__ );
    
    // get this participant's interview or create a new one if none exists yet
    $modifier = new db\modifier();
    $modifier->where( 'participant_id', '=', $db_participant->id );
    $modifier->where( 'qnaire_id', '=', $db_participant->current_qnaire_id );

    $db_interview_list = db\interview::select( $modifier );
    
    if( 0 == count( $db_interview_list ) )
    {
      $db_interview = new db\interview();
      $db_interview->participant_id = $db_participant->id;
      $db_interview->qnaire_id = $db_participant->current_qnaire_id;
      $db_interview->save();
    }
    else
    {
      $db_interview = $db_interview_list[0];
    }

    // create an assignment for this user
    $db_assignment = new db\assignment();
    $db_assignment->user_id = $session->get_user()->id;
    $db_assignment->site_id = $session->get_site()->id;
    $db_assignment->interview_id = $db_interview->id;
    $db_assignment->queue_id = $db_origin_queue->id;
    $db_assignment->save();

    if( $db_origin_queue->from_appointment() )
    { // if this is an appointment queue, mark the appointment now associated with the appointment
      // this should always be the appointment with the earliest date
      $mod = new db\modifier();
      $mod->where( 'assignment_id', '=', NULL );
      $mod->order( 'datetime' );
      $mod->limit( 1 );
      $appointment_list = $db_participant->get_appointment_list( $mod );

      if( 0 == count( $appointment_list ) )
      {
        log::crit(
          'Participant queue is from an appointment but no appointment is found.', __METHOD__ );
      }
      else
      {
        $db_appointment = current( $appointment_list );
        $db_appointment->assignment_id = $db_assignment->id;
        $db_appointment->save();
      }
    }
  }
}
?>
