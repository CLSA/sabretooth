<?php
/**
 * user_new.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui;

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
    parent::execute();

    if( !is_null( $this->site_id ) && !is_null( $this->role_id ) )
    { // add the initial role to the new user
      $columns = $this->get_argument( 'columns' );
      $db_user = \sabretooth\database\user::get_unique_record( 'name', $columns['name'] );
      $db_access = new \sabretooth\database\access();
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
