<?php
/**
 * self_set_role.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui;

/**
 * action self set_role
 * 
 * Changes the current user's role.
 * Arguments must include 'role'.
 * @package sabretooth\ui
 */
class self_set_role extends action
{
  /**
   * Constructor.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args Action arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'self', 'set_role', $args );
    $this->role_name = $this->get_argument( 'role' ); // must exist
  }
  
  /**
   * Executes the action.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @throws exception\runtime
   * @access public
   */
  public function execute()
  {
    $db_role = \sabretooth\database\role::get_unique_record( 'name', $this->role_name );
    if( NULL == $db_role )
      throw new \sabretooth\exception\runtime(
        'Invalid role name "'.$this->role_name.'"', __METHOD__ );

    // get the first role associated with the role
    $session = \sabretooth\session::self();
    $db_site = $session->get_site();
    $db_role_list = $session->get_user()->get_role_list( $db_site );
    if( !in_array( $db_role, $db_role_list ) )
      \sabretooth\log::error( 'User has no access to role name "'.$this->role_name. '"' );

    $session::self()->set_site_and_role( $db_site, $db_role );
  }
  
  /**
   * The name of the role to set.
   * @var string
   * @access protected
   */
  protected $role_name = NULL;
}
?>
