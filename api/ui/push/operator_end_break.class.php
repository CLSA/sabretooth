<?php
/**
 * operator_end_break.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui\push;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * push: operator end_break
 *
 * Start the current user on a break (away_time)
 */
class operator_end_break extends \cenozo\ui\push
{
  /**
   * Constructor.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args Push arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'operator', 'end_break', $args );
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
    $db_user = $session->get_user();
    $db_site = $session->get_site();
    $db_role = $session->get_role();

    // find this user's open break and record the end time
    $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'site_id', '=', $db_site->id );
    $modifier->where( 'role_id', '=', $db_role->id );
    $modifier->where( 'end_datetime', '=', NULL );
    $away_time_list = $db_user->get_away_time_list( $modifier );
    
    // report an error of there isn't exactly 1 one open away time
    if( 1 != count( $away_time_list ) )
      log::alert( sprintf(
        'When attempting to close away time, user "%s" has %d instead of 1 open away times!',
        $db_user->name,
        count( $away_time_list ) ) );
    
    foreach( $away_time_list as $db_away_time )
    {
      $date_obj = util::get_datetime_object();
      $db_away_time->end_datetime = $date_obj->format( 'Y-m-d H:i:s' );
      $db_away_time->save();
    }
  }
}
?>
