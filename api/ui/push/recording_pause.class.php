<?php
/**
 * recording_pause.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui\push;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * push: recording pause
 * 
 * Plays a recording via SIP
 * @package sabretooth\ui
 */
class recording_pause extends \cenozo\ui\push
{
  /**
   * Constructor.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args Push arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'recording', 'pause', $args );
  }

  /**
   * Executes the push.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  // TODO: confirm that this finish needs to be here and if it
  // does, why isnt the parent::finish from cenozo\operation class called?
  public function finish()
  {
    // connect voip to phone
    //bus\voip_manager::self()->call( $db_phone );
  }
}
?>
