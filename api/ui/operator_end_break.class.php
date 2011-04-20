<?php
/**
 * operator_end_break.class.php
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
 * action operator end_break
 *
 * Start the current user on a break (away_time)
 * @package sabretooth\ui
 */
class operator_end_break extends action
{
  /**
   * Constructor.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args Action arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'operator', 'end_break', $args );
  }
  
  /**
   * Executes the action.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function execute()
  {
    $db_user = bus\session::self()->get_user();

    // find this user's open break and record the end time
    $modifier = new db\modifier();
    $modifier->where( 'end_time', '=', NULL );
    $away_time_list = $db_user->get_away_time_list( $modifier );
    
    // report an error of there isn't exactly 1 one open away time
    if( 1 != count( $away_time_list ) )
      log::alert( sprintf(
        'When attempting to close away time, user "%s" has %d instead of 1 open away times!',
        $db_user->name,
        count( $away_time_list ) ) );
    
    foreach( $away_time_list as $db_away_time )
    {
      $db_away_time->end_time = date( 'Y-m-d H:i:s' );
      $db_away_time->save();
    }
  }
}
?>
