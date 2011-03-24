<?php
/**
 * phone_call_end.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui;

/**
 * action phone_call end
 *
 * Assigns a participant to an phone_call.
 * @package sabretooth\ui
 */
class phone_call_end extends action
{
  /**
   * Constructor.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args Action arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'phone_call', 'end', $args );
  }

  /**
   * Executes the action.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function execute()
  {
    $is_operator = 'operator' == \sabretooth\session::self()->get_role()->name;

    // TODO: PHPAGI code to disconnect goes here

    if( $is_operator )
    { // set the end time and status of the call
      $db_phone_call = \sabretooth\session::self()->get_current_phone_call();
      if( !is_null( $db_phone_call ) )
      {
        $db_phone_call->end_time = date( 'Y-m-d H:i:s' );
        $db_phone_call->status = $this->get_argument( 'status' );
        $db_phone_call->save();
      }
    }
  }
}
?>
