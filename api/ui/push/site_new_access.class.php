<?php
/**
 * site_new_access.class.php
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
 * push: site new_access
 * 
 * @package sabretooth\ui
 */
class site_new_access extends base_new_record
{
  /**
   * Constructor.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args Push arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'site', 'access', $args );
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

    // replace the site id with a unique key
    $db_site = $this->get_record();
    unset( $args['id'] );
    $args['noid']['site.name'] = $db_site->name;
    $args['noid']['site.cohort'] = 'tracking';
    
    foreach( $this->get_argument( 'role_id_list' ) as $role_id )
    {
      $this->get_record()->add_access( $this->get_argument( 'user_id_list' ), $role_id );

      // build a list of role names for mastodon
      $db_role = new db\role( $role_id );
      $role_name_list[] = $db_role->name;
    }

    // build a list of user names for mastodon
    foreach( $this->get_argument( 'user_id_list' ) as $user_id )
    {
      $db_user = new db\user( $user_id );
      $user_name_list[] = $db_user->name;
    }

    unset( $args['role_id_list'] );
    unset( $args['user_id_list'] );
    $args['noid']['role_name_list'] = $role_name_list;
    $args['noid']['user_name_list'] = $user_name_list;
  
    // now send the same request to mastodon
    $mastodon_manager = bus\mastodon_manager::self();
    $mastodon_manager->push( 'site', 'new_access', $args );
  }
}
?>
