<?php
/**
 * phone_call_begin.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui;

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
    $session = \sabretooth\business\session::self();
    $is_operator = 'operator' == $session->get_role()->name;
    
    $db_contact = new \sabretooth\database\contact( $this->get_argument( 'contact_id' ) );
    $appointment_id = $this->get_argument( 'appointment_id', NULL );
    $db_assignment = NULL;

    if( $is_operator )
    { // make sure that operators are calling their current assignment only
      $db_assignment = $session->get_current_assignment();
  
      if( is_null( $db_assignment ) )
        throw new \sabretooth\exception\runtime(
          'Operator tried to make call without an assignment.', __METHOD__ );

      if( $db_contact->participant_id != $db_assignment->get_interview()->participant_id )
        throw new \sabretooth\exception\runtime(
          'Operator tried to make call to participant who is not currently assigned.', __METHOD__ );
    }
    
    // connect voip to contact
    \sabretooth\business\voip_manager::self()->call( $db_contact );

    if( $is_operator )
    { // create a record of the phone call
      $db_phone_call = new \sabretooth\database\phone_call();
      $db_phone_call->assignment_id = $db_assignment->id;
      $db_phone_call->contact_id = $db_contact->id;
      $db_phone_call->appointment_id = $appointment_id;
      $db_phone_call->save();
  
      if( is_null( $db_phone_call->id ) )
        throw new \sabretooth\exception\runtime(
          'Failed to create new phone_call.', __METHOD__ );
    }
  }
}
?>
