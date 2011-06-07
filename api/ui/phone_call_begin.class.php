<?php
/**
 * phone_call_begin.class.php
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
 * action phone_call begin
 *
 * Assigns a participant to a phone call.
 * @package sabretooth\ui
 */
class phone_call_begin extends action
{
  /**
   * Constructor.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args Action arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'phone_call', 'begin', $args );
  }

  /**
   * Executes the action.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function execute()
  {
    $session = bus\session::self();
    $is_operator = 'operator' == $session->get_role()->name;
    
    $db_phone = new db\phone( $this->get_argument( 'phone_id' ) );
    $db_assignment = NULL;

    if( $is_operator )
    { // make sure that operators are calling their current assignment only
      $db_assignment = $session->get_current_assignment();
  
      if( is_null( $db_assignment ) )
        throw new exc\runtime(
          'Operator tried to make call without an assignment.', __METHOD__ );

      if( $db_phone->participant_id != $db_assignment->get_interview()->participant_id )
        throw new exc\runtime(
          'Operator tried to make call to participant who is not currently assigned.', __METHOD__ );
    }
    
    // connect voip to phone
    bus\voip_manager::self()->call( $db_phone );

    if( $is_operator )
    { // create a record of the phone call
      $db_phone_call = new db\phone_call();
      $db_phone_call->assignment_id = $db_assignment->id;
      $db_phone_call->phone_id = $db_phone->id;
      $db_phone_call->save();
    }
  }
}
?>
