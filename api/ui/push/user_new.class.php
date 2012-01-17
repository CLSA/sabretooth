<?php
/**
 * user_new.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui\push;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * push: user new
 *
 * Create a new user.
 * @package sabretooth\ui
 */
class user_new extends \cenozo\ui\push\user_new
{
  /**
   * Executes the push.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   * @throws exception\notice
   */
  public function finish()
  {
    parent::finish();

    // need this for mastodon, below
    $args = $this->arguments;
    $args['ignore_existing'] = true;

    if( !is_null( $this->site_id ) && !is_null( $this->role_id ) )
    { // add the initial role to the new user
      $db_site = lib::create( 'database\site', $this->site_id );
      $db_role = lib::create( 'database\role', $this->role_id );

      // add the site, cohort and role to the arguments for mastodon
      $args['noid']['site.name'] = $db_site->name;
      $args['noid']['site.cohort'] = 'tracking';
      $args['noid']['role.name'] = $db_role->name;
    }

    // now send the same request to mastodon
    $mastodon_manager = lib::create( 'business\cenozo_manager', MASTODON_URL );
    $mastodon_manager->push( 'user', 'new', $args );
  }
}
?>
