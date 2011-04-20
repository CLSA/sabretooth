<?php
/**
 * assignment_end.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui;
use sabretooth\log, sabretooth\util;
use sabretooth\business as bus;
use sabretooth\database as db;
use sabretooth\exception as exc;

/**
 * action assignment end
 *
 * Assigns a participant to an assignment.
 * @package sabretooth\ui
 */
class assignment_end extends action
{
  /**
   * Constructor.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args Action arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'assignment', 'end', $args );
  }

  /**
   * Executes the action.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function execute()
  {
    $session = bus\session::self();
    $db_assignment = $session->get_current_assignment();
    if( !is_null( $db_assignment ) )
    {
      // make sure the operator isn't on call
      if( !is_null( $session->get_current_phone_call() ) )
        throw new exc\notice(
          'An assignment cannot be ended while in a call.', __METHOD__ );
      
      // if there is an appointment associated with this assignment, set the status
      $appointment_list = $db_assignment->get_appointment_list();
      if( 0 < count( $appointment_list ) )
      {
        // there should always only be one appointment per assignment
        if( 1 < count( $appointment_list ) )
          log::crit(
            sprintf( 'Assignment %d has more than one associated appointment!',
                     $db_assignment->id ) );

        $db_appointment = current( $appointment_list );

        // set the appointment status based on whether any calls reached the participant
        $modifier = new db\modifier();
        $modifier->where( 'status', '=', 'contacted' );
        $db_appointment->status = 
          0 < $db_assignment->get_phone_call_count( $modifier ) ? 'complete' : 'incomplete';
        $db_appointment->save();
      }

      // save the assignment's end time
      $db_assignment->end_time = date( 'Y-m-d H:i:s' );
      $db_assignment->save();
    }
  }
}
?>
