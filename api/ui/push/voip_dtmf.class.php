<?php
/**
 * voip_dtmf.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui\push;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * push: voip dtmf
 *
 * Changes the current user's theme.
 * Arguments must include 'theme'.
 */
class voip_dtmf extends \cenozo\ui\push
{
  /**
   * Constructor.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args Push arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'voip', 'dtmf', $args );
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

    $voip_call = lib::create( 'business\voip_manager' )->get_call();
    if( is_null( $voip_call ) )
      throw lib::create( 'exception\notice',
        'Unable to send tone since you are not currently in a call.', __NOTICE__ );
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

    $voip_call = lib::create( 'business\voip_manager' )->get_call();
    $voip_call->dtmf( $this->get_argument( 'tone' ) );
  }
}
?>
