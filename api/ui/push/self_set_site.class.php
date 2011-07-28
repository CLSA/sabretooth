<?php
/**
 * self_set_site.class.php
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
 * push: self set_site
 *
 * Changes the current user's site.
 * Arguments must include 'site'.
 * @package sabretooth\ui
 */
class self_set_site extends \sabretooth\ui\push
{
  /**
   * Constructor.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args Push arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'self', 'set_site', $args );
  }
  
  /**
   * Executes the push.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @throws exception\runtime
   * @access public
   */
  public function finish()
  {
    try
    {
      $db_site = new db\site( $this->get_argument( 'id' ) );
    }
    catch( exc\runtime $e )
    {
      throw new exc\argument( 'id', $this->get_argument( 'id' ), __METHOD__, $e );
    }

    // get the first role associated with the site
    $modifier = new db\modifier();
    $modifier->where( 'site_id', '=', $db_site->id );
    $session = bus\session::self();
    $db_role_list = $session->get_user()->get_role_list( $modifier );
    if( 0 == count( $db_role_list ) )
      throw new exc\runtime(
        'User does not have access to the given site.',  __METHOD__ );

    $session::self()->set_site_and_role( $db_site, $db_role_list[0] );
  }
}
?>
