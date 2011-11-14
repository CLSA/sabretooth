<?php
/**
 * system_message_edit.class.php
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
 * push: system_message edit
 *
 * Edit a system_message.
 * @package sabretooth\ui
 */
class system_message_edit extends base_edit
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
    // make sure that only top tier roles can edit system messages not belonging to the current site
    $session = bus\session::self();

    if( 3 != $session->get_role()->tier && $session->get_site()->id != $this->get_record()->site_id )
    {
      throw new exc\notice(
        'You do not have access to edit this system message.', __METHOD__ );
    }

    parent::finish();
  }
}
?>
