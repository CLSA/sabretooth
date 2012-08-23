<?php
/**
 * phone_call_begin.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui\push;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * push: phone_call begin
 *
 * Assigns a participant to a phone call.
 */
class phone_call_begin extends \cenozo\ui\push
{
  /**
   * Constructor.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args Push arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'phone_call', 'begin', $args );
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
    $session = lib::create( 'business\session' );

    $phone_id = $this->get_argument( 'phone_id', NULL );
    if( !is_null( $phone_id ) && 'operator' == $session->get_role()->name )
    { // make sure that operators are calling their current assignment only
      $db_phone = lib::create( 'database\phone', $phone_id );
      $db_assignment = $session->get_current_assignment();
  
      if( is_null( $db_assignment ) )
        throw lib::create( 'exception\runtime',
          'Operator tried to make call without an assignment.', __METHOD__ );

      if( $db_phone->participant_id != $db_assignment->get_interview()->participant_id )
        throw lib::create( 'exception\runtime',
          'Operator tried to make call to participant who is not currently assigned.', __METHOD__ );
    }

    $phone_number = $this->get_argument( 'phone_number', NULL );
    if( !is_null( $phone_number ) )
    { // make sure the phone number is valid
      if( !util::validate_phone_number( $phone_number, true ) )
        throw lib::create( 'exception\notice',
          sprintf( 'Cannot dial phone number "%s" since it is not a valid.', $phone_number ),
          __METHOD__ );
    }
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

    $session = lib::create( 'business\session' );
    $is_operator = 'operator' == $session->get_role()->name;
    
    // connect voip to phone
    $phone_id = $this->get_argument( 'phone_id', NULL );
    if( !is_null( $phone_id ) )
    {
      $db_phone = lib::create( 'database\phone', $this->get_argument( 'phone_id' ) );
      lib::create( 'business\voip_manager' )->call( $db_phone );

      if( $is_operator )
      { // create a record of the phone call
        $db_assignment = $session->get_current_assignment();
        $db_phone_call = lib::create( 'database\phone_call' );
        $db_phone_call->assignment_id = $db_assignment->id;
        $db_phone_call->phone_id = $db_phone->id;
        $db_phone_call->save();
      }
    }
    else // must be a phone number
    {
      lib::create( 'business\voip_manager' )->call( $this->get_argument( 'phone_number' ) );
    }
  }
}
?>
