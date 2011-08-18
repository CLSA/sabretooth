<?php
/**
 * user_new_access.class.php
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
 * push: user new_access
 * 
 * @package sabretooth\ui
 */
class user_new_access extends base_new_record
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

    // replace the user's id with their name
    $db_user = new db\user( $this->get_argument('id') );
    unset( $args['id'] );

    $args['user'] = $db_user->name;
    
    foreach( $this->get_argument( 'role_id_list' ) as $role_id )
    {
      $this->get_record()->add_access( $this->get_argument( 'site_id_list' ), $role_id );

      // build a list of role names for mastodon
      $db_role = new db\role( $role_id );
      $role_name_list[] = $db_role->name;
    }

    // build a list of site names for mastodon
    foreach( $this->get_argument( 'site_id_list' ) as $site_id )
    {
      $db_site = new db\site( $site_id );
      $site_name_list[] = array( 'name' => $db_site->name, 'cohort' => 'tracking' );
    }


    unset( $args['role_id_list'] );
    unset( $args['site_id_list'] );
    $args['role_name_list'] = $role_name_list;
    $args['site_name_list'] = $site_name_list;
  
    log::debug( $args );
    // now send the same request to mastodon
    $mastodon_manager = bus\mastodon_manager::self();
    $mastodon_manager->push( 'user', 'new_access', $args );
  }
}
?>
