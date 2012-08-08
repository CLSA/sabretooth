<?php
/**
 * voip_begin_monitor.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui\push;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * push: voip begin_monitor
 *
 * Changes the current user's theme.
 * Arguments must include 'theme'.
 */
class voip_begin_monitor extends \cenozo\ui\push
{
  /**
   * Constructor.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args Push arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'voip', 'begin_monitor', $args );
  }
  
  /**
   * This method executes the operation's purpose.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access protected
   */
  protected function execute()
  {
    parent::execute();

    lib::create( 'business\voip_manager' )->get_call()->start_monitoring(
      lib::create( 'business\survey_manager' )->get_current_token() );
  }
}
?>
