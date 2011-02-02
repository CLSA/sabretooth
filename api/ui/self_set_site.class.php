<?php
/**
 * self_set_site.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 */

namespace sabretooth\ui;

/**
 * self.set_site action
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
  public function __construct( $args = NULL )
  {
    parent::__construct( 'self', 'set_site', $args );
    
    // grab expected arguments
    if( is_array( $args ) && array_key_exists( 'site', $args ) )
      $this->site_name = $args['site'];
    
    // make sure we have all the arguments necessary
    if( !isset( $this->site_name ) )
      throw new \sabretooth\exception\argument( 'site' );
  }
  
  /**
   * Executes the action.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function execute()
  {
    $db_site = \sabretooth\database\site::get_unique_record( 'name', $this->site_name );
    if( NULL == $db_site )
      throw new \sabretooth\exception\runtime(
        'Invalid site name "'.$this->site_name.'"' );

    // get the first role associated with the site
    $session = \sabretooth\session::self();
    $db_role_array = $session->get_user()->get_roles( $db_site );
    if( 0 == count( $db_role_array ) )
      throw new \sabretooth\exception\runtime(
        'User has no access to site name "'.$this->site_name.'"' );

    $session::self()->set_site_and_role( $db_site, $db_role_array[0] );
  }

  /**
   * The name of the site to set.
   * @var string
   * @access protected
   */
  protected $site_name = NULL;
}
?>
