<?php
/**
 * operator_new_assignment.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui;

/**
 * action operator new_assignment
 *
 * Assigns a participant to an operator.
 * @package sabretooth\ui
 */
class operator_new_assignment extends action
{
  /**
   * Constructor.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args Action arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'operator', $args );
  }

  /**
   * Executes the action.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function execute()
  {
    $modifier = new \sabretooth\database\modifier();
    $modifier->limit( 1 );

    // create the queues, then go search for a new assignment in each one until one is found
    $db_participant = NULL;
    foreach( \sabretooth\database\queue::select() as $db_queue )
    {
      $db_queue->set_site( \sabretooth\session::self()->get_site() );
      $participant_list = $db_queue->get_participant_list( $modifier );
      if( 1 == count( $participant_list ) )
      {
        $db_participant = current( $participant_list );
        break;
      }
    }

    if( is_null( $db_participant ) )
      throw new \sabretooth\exception\notice(
        'There are no participants currently available.', __METHOD__ );
    
    // assign the participant to this operator
    /* TODO: finish
    $db_interview = new \sabretooth\database\interview();
    $db_interview->participant_id = $db_participant->id;
    $db_interview->phase_id = 
   */

    parent::execute();
  }
}
?>
