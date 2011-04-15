<?php
/**
 * assignment_begin.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui;

/**
 * action assignment begin
 *
 * Assigns a participant to an assignment.
 * @package sabretooth\ui
 */
class assignment_begin extends action
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
  public function execute()
  {
    $session = \sabretooth\business\session::self();

    // search through every queue for a new assignment until one is found
    $modifier = new \sabretooth\database\modifier();
    $modifier->where( 'rank', '!=', NULL );
    $modifier->order( 'rank' );
    $db_origin_queue = NULL;
    $db_participant = NULL;
    $db_appointment_id = NULL;
    foreach( \sabretooth\database\queue::select( $modifier ) as $db_queue )
    {
      $mod = new \sabretooth\database\modifier();
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
      throw new \sabretooth\exception\notice(
        'There are no participants currently available.', __METHOD__ );
    
    $db_sample = $db_participant->get_active_sample();
    
    if( is_null( $db_sample ) )
      throw new \sabretooth\exception\runtime(
        'Participant in queue has no active sample.', __METHOD__ );

    // create an interview for the participant
    $db_interview = new \sabretooth\database\interview();
    $db_interview->participant_id = $db_participant->id;
    $db_interview->qnaire_id = $db_sample->qnaire_id;
    $db_interview->save();

    // create an assignment for this user
    $db_assignment = new \sabretooth\database\assignment();
    $db_assignment->user_id = $session->get_user()->id;
    $db_assignment->site_id = $session->get_site()->id;
    $db_assignment->interview_id = $db_interview->id;
    $db_assignment->queue_id = $db_origin_queue->id;
    $db_assignment->save();

    if( $db_origin_queue->from_appointment() )
    { // if this is an appointment queue, mark the appointment now associated with the appointment
      // this should always be the appointment with the earliest date
      $mod = new \sabretooth\database\modifier();
      $mod->where( 'assignment_id', '=', NULL );
      $mod->order( 'date' );
      $mod->limit( 1 );
      $appointment_list = $db_participant->get_appointment_list( $mod );

      if( 0 == count( $appointment_list ) )
      {
        \sabretooth\log::crit(
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
