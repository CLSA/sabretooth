<?php
/**
 * assignment_end.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui;

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
    $session = \sabretooth\session::self();
    $db_assignment = $session->get_current_assignment();
    if( !is_null( $db_assignment ) )
    {
      // make sure the operator isn't on call
      if( !is_null( $session->get_current_phone_call() ) )
        throw new \sabretooth\exception\notice(
          'An assignment cannot be ended while in a call.', __METHOD__ );

      $db_assignment->end_time = date( 'Y-m-d H:i:s' );
      $db_assignment->save();
    }
  }
}
?>
