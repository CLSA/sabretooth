<?php
/**
 * self_set_password.class.php
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
 * push: self set_password
 * 
 * Changes the current user's password.
 * Arguments must include 'password'.
 * @package sabretooth\ui
 */
class self_set_password extends \sabretooth\ui\push
{
  /**
   * Constructor.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args Push arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'self', 'set_password', $args );
  }
  
  /**
   * Executes the push.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @throws exception\runtime
   * @access public
   */
  public function finish()
  {
    $db_user = bus\session::self()->get_user();
    $old = $this->get_argument( 'old', 'password' );
    $new = $this->get_argument( 'new' );
    $confirm = $this->get_argument( 'confirm' );
    
    // make sure the old password is correct
    $ldap_manager = bus\ldap_manager::self();
    if( !$ldap_manager->validate_user( $db_user->name, $old ) )
      throw new exc\notice( 'The password you have provided is incorrect.', __METHOD__ );
    
    // make sure the new password isn't blank, at least 6 characters long and not "password"
    if( 6 > strlen( $new ) )
      throw new exc\notice( 'Passwords must be at least 6 characters long.', __METHOD__ );
    else if( 'password' == $new )
      throw new exc\notice( 'You cannot choose "password" as your password.', __METHOD__ );
    
    // and that the user confirmed their new password correctly
    if( $new != $confirm )
      throw new exc\notice(
        'The confirmed password does not match your new password.', __METHOD__ );

    $ldap_manager->set_user_password( $db_user->name, $new );

    // now flush the voip account
    bus\voip_manager::self()->sip_prune( $db_user->name );
  }
  
  /**
   * The name of the password to set.
   * @var string
   * @access protected
   */
  protected $password_name = NULL;
}
?>
