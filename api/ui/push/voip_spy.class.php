<?php
/**
 * voip_spy.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui\push;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * push: voip spy
 *
 * Changes the current user's theme.
 * Arguments must include 'theme'.
 * @package sabretooth\ui
 */
class voip_spy extends \cenozo\ui\push
{
  /**
   * Constructor.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args Push arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'voip', 'spy', $args );
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

    $db_user = lib::create( 'database\user', $this->get_argument( 'user_id' ) );
    if( is_null( $db_user ) )
      throw lib::create( 'exception\notice',
        'Unable to find operator to connect to.', __METHOD__ );

    $this->voip_call = lib::create( 'business\voip_manager' )->get_call( $db_user );
    if( is_null( $this->voip_call ) )
      throw lib::create( 'exception\notice',
        sprintf( 'Cannot listen in to user "%s", not currently in a call.', $db_user->name ),
        __METHOD__ );
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

    lib::create( 'business\voip_manager' )->spy( $this->voip_call );
  }

  /**
   * The voip call to spy on.
   * @var business\voip_call $voip_call
   * @access protected
   */
  protected $voip_call = NULL;
}
?>
