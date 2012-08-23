<?php
/**
 * opal_instance_new.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui\push;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * push: opal_instance new
 *
 * Create a new opal_instance.
 */
class opal_instance_new extends \cenozo\ui\push\base_new
{
  /**
   * Constructor.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args Push arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'opal_instance', $args );
  }

  /**
   * Validate the operation.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @throws exception\notice
   * @access protected
   */
  protected function validate()
  {
    parent::validate();

    $columns = $this->get_argument( 'columns' );

    // make sure that the username is not empty
    if( !$columns['username'] )
      throw lib::create( 'exception\notice',
        'The opal instance\'s user name cannot be left blank.', __METHOD__ );
    
    if( !$columns['password'] )
      throw lib::create( 'exception\notice',
        'You must provide a password at least 6 characters long.', __METHOD__ );
    
    if( 6 > strlen( $columns['password'] ) )
      throw lib::create( 'exception\notice',
        'Passwords must be at least 6 characters long.', __METHOD__ );

    if( 'password' == $columns['password'] )
      throw lib::create( 'exception\notice',
        'You cannot choose "password" as a password.', __METHOD__ );
  }

  /**
   * Sets up the operation with any pre-execution instructions that may be necessary.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access protected
   */
  protected function setup()
  {
    parent::setup();

    $columns = $this->get_argument( 'columns' );

    $db_site = lib::create( 'business\session' )->get_site();
    $class_name = lib::get_class_name( 'database\role' );
    $db_role = $class_name::get_unique_record( 'name', 'opal' );

    // now create the user and add opal access to it
    $args = array( 'columns' =>
              array(
                'name' => $columns['username'],
                'first_name' => $db_site->name.' opal instance',
                'last_name' => $columns['username'],
                'active' => true,
                'role_id' => $db_role->id,
                'site_id' => $db_site->id ) );
    $operation = lib::create( 'ui\push\user_new', $args );
    
    $operation->process();

    // get the newly created user and set its password
    $class_name = lib::get_class_name( 'database\user' );
    $db_user = $class_name::get_unique_record( 'name', $columns['username'] );
    lib::create( 'business\ldap_manager' )->set_user_password(
      $db_user->name, $columns['password'] );
    
    // replace the username argument with the newly created user id for the new opal instance
    unset( $this->arguments['columns']['username'] );
    unset( $this->arguments['columns']['password'] );
    $this->arguments['columns']['user_id'] = $db_user->id;
  }
}
?>
