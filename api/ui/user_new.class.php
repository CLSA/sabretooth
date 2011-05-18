<?php
/**
 * user_new.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui;
use sabretooth\log, sabretooth\util;
use sabretooth\business as bus;
use sabretooth\database as db;
use sabretooth\exception as exc;

/**
 * action user new
 *
 * Create a new user.
 * @package sabretooth\ui
 */
class user_new extends base_new
{
  /**
   * Constructor.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args Action arguments
   * @access public
   */
  public function __construct( $args )
  {
    // remove the role id from the columns and use it to create the user's initial role
    if( isset( $args['columns'] ) &&
        isset( $args['columns']['role_id'] ) && isset( $args['columns']['site_id'] ) )
    {
      $this->role_id = $args['columns']['role_id'];
      $this->site_id = $args['columns']['site_id'];
      unset( $args['columns']['role_id'] );
      unset( $args['columns']['site_id'] );
    }

    parent::__construct( 'user', $args );
  }

  /**
   * Executes the action.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function execute()
  {
    $columns = $this->get_argument( 'columns' );

    // add the user to ldap
    $ldap_manager = bus\ldap_manager::self();
    try
    {
      $ldap_manager->new_user(
        $columns['name'], $columns['first_name'], $columns['last_name'], 'password' );
    }
    catch( exc\ldap $e )
    {
      // catch already exists exceptions, no need to report them
      if( !$e->is_already_exists() ) throw $e;
    }

    parent::execute();

    if( !is_null( $this->site_id ) && !is_null( $this->role_id ) )
    { // add the initial role to the new user
      $db_user = db\user::get_unique_record( 'name', $columns['name'] );
      $db_access = new db\access();
      $db_access->user_id = $db_user->id;
      $db_access->site_id = $this->site_id;
      $db_access->role_id = $this->role_id;
      $db_access->save();
    }
  }

  /**
   * The initial site to give the new user access to
   * @var int
   * @access protected
   */
  protected $site_id = NULL;

  /**
   * The initial role to give the new user
   * @var int
   * @access protected
   */
  protected $role_id = NULL;
}
?>
