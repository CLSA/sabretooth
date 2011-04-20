<?php
/**
 * operator_begin_break.class.php
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
 * action operator begin_break
 *
 * Start the current user on a break (away_time)
 * @package sabretooth\ui
 */
class operator_begin_break extends action
{
  /**
   * Constructor.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args Action arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'operator', 'begin_break', $args );
  }
  
  /**
   * Executes the action.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function execute()
  {
    $session = bus\session::self();
    $db_away_time = new db\away_time();
    $db_away_time->user_id = $session->get_user()->id;
    $db_away_time->save();
  }
}
?>
