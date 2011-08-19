<?php
/**
 * user_delete_access.class.php
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
 * push: user delete_access
 * 
 * @package sabretooth\ui
 */
class user_delete_access extends base_delete_record
{
  /**
   * Constructor.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args Push arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'user', 'access', $args );
  }
  
  /**
   * Executes the push.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function finish()
  {
    // we'll need the arguments to send to mastodon
    $args = $this->arguments;

    // replace the user id with a unique key
    $db_user = new db\user( $this->get_argument('id') );
    unset( $args['id'] );
    $args['noid']['user.name'] = $db_user->name;
    
    // replace the access id with identifying names of the unique key
    $db_access = new db\access( $this->get_argument('remove_id') );
    unset( $args['remove_id'] );
    $args['noid']['role.name'] = $db_access->get_role()->name;
    $args['noid']['site.name'] = $db_access->get_site()->name;
    $args['noid']['site.cohort'] = 'tracking';
    
    parent::finish();

    // now send the same request to mastodon
    $mastodon_manager = bus\mastodon_manager::self();
    $mastodon_manager->push( 'user', 'delete_access', $args );
  }
}
?>
