<?php
/**
 * access_delete.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui\push;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * push: access delete
 * 
 * @package sabretooth\ui
 */
class access_delete extends \cenozo\ui\push\access_delete
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

    // replace the access id with a unique key
    $db_access = $this->get_record();
    unset( $args['id'] );
    $args['noid']['user.name'] = $db_access->get_user()->name;
    $args['noid']['role.name'] = $db_access->get_role()->name;
    $args['noid']['site.name'] = $db_access->get_site()->name;
    $args['noid']['site.cohort'] = 'tracking';

    parent::finish();

    // now send the same request to mastodon
    $mastodon_manager = lib::create( 'business\cenozo_manager', MASTODON_URL );
    $mastodon_manager->push( 'access', 'delete', $args );
  }
}
?>
