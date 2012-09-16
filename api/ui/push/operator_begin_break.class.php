<?php
/**
 * operator_begin_break.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui\push;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * push: operator begin_break
 *
 * Start the current user on a break (away_time)
 */
class operator_begin_break extends \cenozo\ui\push
{
  /**
   * Constructor.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args Push arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'operator', 'begin_break', $args );
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

    $session = lib::create( 'business\session' );
    $db_away_time = lib::create( 'database\away_time' );
    $db_away_time->user_id = $session->get_user()->id;
    $db_away_time->site_id = $session->get_site()->id;
    $db_away_time->role_id = $session->get_role()->id;
    $db_away_time->save();
  }
}
?>
