<?php
/**
 * self_set_password.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui\push;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * push: self set_password
 * 
 * Changes the current user's password.
 * Arguments must include 'password'.
 * @package sabretooth\ui
 */
class self_set_password extends \cenozo\ui\push\self_set_password
{
  /**
   * Executes the push.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @throws exception\runtime
   * @access public
   */
  public function finish()
  {
    parent::finish();

    // flush the voip account
    lib::create( 'business\voip_manager' )->sip_prune(
      lib::create( 'business\session' )->get_user() );
  }
}
?>
