<?php
/**
 * self_set_site.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui;

/**
 * action self set_site
 *
 * Changes the current user's site.
 * Arguments must include 'site'.
 * @package sabretooth\ui
 */
class self_set_site extends action
{
  /**
   * Constructor.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args Action arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'self', 'set_site', $args );
    $this->site_name = $this->get_argument( 'site' ); // must exist
  }
  
  /**
   * Executes the action.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @throws exception\runtime
   * @access public
   */
  public function execute()
  {
    $db_site = \sabretooth\database\site::get_unique_record( 'name', $this->site_name );
    if( NULL == $db_site )
      throw new \sabretooth\exception\runtime(
        'Invalid site name "'.$this->site_name.'"', __METHOD__ );

    // get the first role associated with the site
    $modifier = new \sabretooth\database\modifier();
    $modifier->where( 'site_id', '=', $db_site->id );
    $session = \sabretooth\business\session::self();
    $db_role_list = $session->get_user()->get_role_list( $modifier );
    if( 0 == count( $db_role_list ) )
      throw new \sabretooth\exception\runtime(
        'User has no access to site name "'.$this->site_name.'"', __METHOD__ );

    $session::self()->set_site_and_role( $db_site, $db_role_list[0] );
  }

  /**
   * The name of the site to set.
   * @var string
   * @access protected
   */
  protected $site_name = NULL;
}
?>
