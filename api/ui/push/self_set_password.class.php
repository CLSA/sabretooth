<?php
/**
 * self_set_password.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui\push;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * push: self set_password
 * 
 * Changes the current user's password.
 * Arguments must include 'password'.
 */
class self_set_password extends \cenozo\ui\push\self_set_password
{
  /**
   * This method executes the operation's purpose.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access protected
   */
  protected function execute()
  {
    parent::execute();

    // flush the voip account
    lib::create( 'business\voip_manager' )->sip_prune(
      lib::create( 'business\session' )->get_user() );
  }
}
?>
