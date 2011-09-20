<?php
/**
 * system_message_delete.class.php
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
 * push: system_message delete
 * 
 * @package sabretooth\ui
 */
class system_message_delete extends base_delete
{
  /**
   * Constructor.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args Push arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'system_message', $args );
  }

  /**
   * Executes the push.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @throws exception\notice
   * @access public
   */
  public function finish()
  {
    // make sure that only admins can remove system messages not belonging to the current site
    $session = bus\session::self();
    $is_administrator = 'administrator' == $session->get_role()->name;

    if( !$is_administrator && $session->get_site()->id != $this->get_record()->site_id )
    {
      throw new exc\notice(
        'You do not have access to remove this system message.', __METHOD__ );
    }

    parent::finish();
  }
}
?>
