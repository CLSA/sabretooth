<?php
/**
 * user_edit.class.php
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
 * push: user edit
 *
 * Edit a user.
 * @package sabretooth\ui
 */
class user_edit extends base_edit
{
  /**
   * Constructor.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args Push arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'user', $args );
  }
  
  /**
   * Extends the base action by sending the same request to Mastodon
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function finish()
  {
    // we'll need the arguments to send to mastodon
    $args = $this->arguments;

    // replace the user's id with their name
    $db_user = new db\user( $this->get_argument('id') );
    unset( $args['id'] );

    $args['user'] = $db_user->name;
    
    parent::finish();

    // now send the same request to mastodon
    $mastodon_manager = bus\mastodon_manager::self();
    $mastodon_manager->push( 'user', 'edit', $args );
  }
}
?>
