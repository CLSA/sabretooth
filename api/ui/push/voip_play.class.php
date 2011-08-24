<?php
/**
 * voip_play.class.php
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
 * push: voip play
 *
 * Changes the current user's theme.
 * Arguments must include 'theme'.
 * @package sabretooth\ui
 */
class voip_play extends \sabretooth\ui\push
{
  /**
   * Constructor.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args Push arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'voip', 'play', $args );
  }
  
  /**
   * Executes the push.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function finish()
  {
    bus\voip_manager::self()->get_call()->play_sound(
      $this->get_argument( 'sound' ),
      $this->get_argument( 'volume' ) );
  }
}
?>
