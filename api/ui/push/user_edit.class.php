<?php
/**
 * user_edit.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui\push;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * push: user edit
 *
 * Edit a user.
 * @package sabretooth\ui
 */
class user_edit extends \cenozo\ui\push\user_edit
{
  /**
   * Constructor.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args Push arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( $args );
    $this->set_machine_request_enabled( true );
    $this->set_machine_request_url( MASTODON_URL );
  }
}
?>
