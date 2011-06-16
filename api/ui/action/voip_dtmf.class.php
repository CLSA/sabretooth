<?php
/**
 * voip_dtmf.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui\action;
use sabretooth\log, sabretooth\util;
use sabretooth\business as bus;
use sabretooth\database as db;
use sabretooth\exception as exc;

/**
 * action voip dtmf
 *
 * Changes the current user's theme.
 * Arguments must include 'theme'.
 * @package sabretooth\ui
 */
class voip_dtmf extends \sabretooth\ui\action
{
  /**
   * Constructor.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args Action arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'voip', 'dtmf', $args );
  }
  
  /**
   * Executes the action.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function execute()
  {
    bus\voip_manager::self()->get_call()->dtmf( $this->get_argument( 'tone' ) );
  }
}
?>
