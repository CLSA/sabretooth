<?php
/**
 * voip_dtmf.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui\push;
use sabretooth\log, sabretooth\util;
use sabretooth\business as bus;
use sabretooth\database as db;
use sabretooth\exception as exc;

/**
 * push: voip dtmf
 *
 * Changes the current user's theme.
 * Arguments must include 'theme'.
 * @package sabretooth\ui
 */
class voip_dtmf extends \sabretooth\ui\push
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
   * Executes the push.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function finish()
  {
    $voip_call = bus\voip_manager::self()->get_call();
    if( is_null( $voip_call ) )
      throw new exc\notice(
        'Unable to send tone since you are not currently in a call.', __NOTICE__ );

    $voip_call->dtmf( $this->get_argument( 'tone' ) );
  }
}
?>
