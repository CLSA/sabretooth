<?php
/**
 * user_new_access.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui\push;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * push: user new_access
 * 
 * @package sabretooth\ui
 */
class user_new_access extends \cenozo\ui\push\user_new_access
{
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
    $db_user = $this->get_record();
    unset( $args['id'] );
    $args['noid']['user.name'] = $db_user->name;
    
    foreach( $this->get_argument( 'role_id_list' ) as $role_id )
    {
      $this->get_record()->add_access( $this->get_argument( 'site_id_list' ), $role_id );

      // build a list of role names for mastodon
      $db_role = lib::create( 'database\role', $role_id );
      $role_name_list[] = $db_role->name;
    }

    // build a list of site names for mastodon
    foreach( $this->get_argument( 'site_id_list' ) as $site_id )
    {
      $db_site = lib::create( 'database\site', $site_id );
      $site_name_list[] = array( 'name' => $db_site->name, 'cohort' => 'tracking' );
    }

    unset( $args['role_id_list'] );
    unset( $args['site_id_list'] );
    $args['noid']['role_name_list'] = $role_name_list;
    $args['noid']['site_name_list'] = $site_name_list;
  
    // now send the same request to mastodon
    $mastodon_manager = lib::create( 'business\cenozo_manager', MASTODON_URL );
    $mastodon_manager->push( 'user', 'new_access', $args );
  }
}
?>
