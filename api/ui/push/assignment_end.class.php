<?php
/**
 * assignment_end.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui\push;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * push: assignment end
 *
 * Assigns a participant to an assignment.
 */
class assignment_end extends \cenozo\ui\push
{
  /**
   * Constructor.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args Push arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'assignment', 'end', $args );
  }

  /**
   * Validate the operation.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @throws exception\notice
   * @access protected
   */
  protected function validate()
  {
    parent::validate();

    // make sure the operator isn't on call
    if( !is_null( lib::create( 'business\session' )->get_current_phone_call() ) )
      throw lib::create( 'exception\notice',
        'An assignment cannot be ended while in a call.', __METHOD__ );
  }

  /**
   * This method executes the operation's purpose.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access protected
   */
  protected function execute()
  {
    parent::execute();

    $db_assignment = lib::create( 'business\session' )->get_current_assignment();
    if( !is_null( $db_assignment ) )
    {
      // if there is an appointment or callback associated with this assignment, set the status
      $appointment_list = $db_assignment->get_appointment_list();
      $callback_list = $db_assignment->get_callback_list();
      if( 0 < count( $appointment_list ) )
      {
        // there should always only be one appointment per assignment
        if( 1 < count( $appointment_list ) )
          log::crit(
            sprintf( 'Assignment %d has more than one associated appointment!',
                     $db_assignment->id ) );

        $db_appointment = current( $appointment_list );

        // set the appointment status based on whether any calls reached the participant
        $modifier = lib::create( 'database\modifier' );
        $modifier->where( 'status', '=', 'contacted' );
        $db_appointment->reached = 0 < $db_assignment->get_phone_call_count( $modifier );
        $db_appointment->save();
      }
      else if( 0 < count( $callback_list ) )
      {
        // there should always only be one callback per assignment
        if( 1 < count( $callback_list ) )
          log::crit(
            sprintf( 'Assignment %d has more than one associated callback!',
                     $db_assignment->id ) );

        $db_callback = current( $callback_list );

        // set the callback status based on whether any calls reached the participant
        $modifier = lib::create( 'database\modifier' );
        $modifier->where( 'status', '=', 'contacted' );
        $db_callback->reached = 0 < $db_assignment->get_phone_call_count( $modifier );
        $db_callback->save();
      }

      // save the assignment's end time
      $date_obj = util::get_datetime_object();
      $db_assignment->end_datetime = $date_obj->format( 'Y-m-d H:i:s' );
      $db_assignment->save();
    }
  }
}
?>
