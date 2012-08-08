<?php
/**
 * voip_play.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui\push;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * push: voip play
 *
 * Changes the current user's theme.
 * Arguments must include 'theme'.
 */
class voip_play extends \cenozo\ui\push
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
   * This method executes the operation's purpose.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access protected
   */
  protected function execute()
  {
    parent::execute();

    lib::create( 'business\voip_manager' )->get_call()->play_sound(
      $this->get_argument( 'sound' ),
      $this->get_argument( 'volume' ) );
  }
}
?>
