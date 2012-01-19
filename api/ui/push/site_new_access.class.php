<?php
/**
 * site_new_access.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui\push;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * push: site new_access
 * 
 * @package sabretooth\ui
 */
class site_new_access extends \cenozo\ui\push\site_new_access
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

    // replace the site id with a unique key
    $db_site = $this->get_record();
    unset( $args['id'] );
    $args['noid']['site.name'] = $db_site->name;
    $args['noid']['site.cohort'] = 'tracking';
    
    foreach( $this->get_argument( 'role_id_list' ) as $role_id )
    {
      $this->get_record()->add_access( $this->get_argument( 'user_id_list' ), $role_id );

      // build a list of role names for mastodon
      $db_role = lib::create( 'database\role', $role_id );
      $role_name_list[] = $db_role->name;
    }

    // build a list of user names for mastodon
    foreach( $this->get_argument( 'user_id_list' ) as $user_id )
    {
      $db_user = lib::create( 'database\user', $user_id );
      $user_name_list[] = $db_user->name;
    }

    unset( $args['role_id_list'] );
    unset( $args['user_id_list'] );
    $args['noid']['role_name_list'] = $role_name_list;
    $args['noid']['user_name_list'] = $user_name_list;
  
    // now send the same request to mastodon
    $mastodon_manager = lib::create( 'business\cenozo_manager', MASTODON_URL );
    $mastodon_manager->push( 'site', 'new_access', $args );
  }
}
?>
