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
    $db_assignment = \sabretooth\session::self()->get_current_assignment();
    if( !is_null( $db_assignment ) )
    {
      $db_assignment->end_time = date( 'Y-m-d H:i:s' );
      $db_assignment->save();
    }
  }
}
?>
