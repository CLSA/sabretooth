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
    $queue_id = NULL;
    $db_participant = NULL;
    foreach( \sabretooth\database\queue::select( $modifier ) as $db_queue )
    {
      $mod = new \sabretooth\database\modifier();
      $mod->limit( 1 );
      $db_queue->set_site( $session->get_site() );
      $participant_list = $db_queue->get_participant_list( $mod );
      if( 1 == count( $participant_list ) )
      {
        $queue_id = $db_queue->id;
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

    if( is_null( $db_interview->id ) )
      throw new \sabretooth\exception\runtime(
        'Failed to create new interview.', __METHOD__ );
    
    // create an assignment for this user
    $db_assignment = new \sabretooth\database\assignment();
    $db_assignment->user_id = $session->get_user()->id;
    $db_assignment->site_id = $session->get_site()->id;
    $db_assignment->interview_id = $db_interview->id;
    $db_assignment->queue_id = $queue_id;
    $db_assignment->save();

    if( is_null( $db_assignment->id ) )
      throw new \sabretooth\exception\runtime(
        'Failed to create new assignment.', __METHOD__ );
  }
}
?>
