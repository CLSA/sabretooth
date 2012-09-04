<?php
/**
 * away_time_new.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui\push;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * push: away_time new
 *
 * Create a new away time.
 */
class away_time_new extends \cenozo\ui\push\base_new
{
  /**
   * Constructor.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args Push arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'away_time', $args );
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
    $db_user = lib::create( 'database\user', $columns['user_id'] );

    // make sure the start and end datetimes aren't blank
    $columns = $this->get_argument( 'columns' );
    if( !array_key_exists( 'start_datetime', $columns ) || 0 == strlen( $columns['start_datetime'] ) )
      throw lib::create( 'exception\notice',
        'The away time\'s start datetime cannot be left blank.', __METHOD__ );
    if( !array_key_exists( 'end_datetime', $columns ) || 0 == strlen( $columns['end_datetime'] ) )
      throw lib::create( 'exception\notice',
        'The away time\'s end datetime cannot be left blank.', __METHOD__ );

    // get the modified start/end datetimes
    $start_datetime = $columns['start_datetime'];
    $end_datetime = $columns['end_datetime'];

    // make sure the datetimes are not in the future
    $datetime_obj = util::get_datetime_object( $start_datetime );
    if( $datetime_obj > util::get_datetime_object() )
      throw lib::create( 'exception\notice', 'Cannot set future start times.', __METHOD__ );
    $datetime_obj = util::get_datetime_object( $end_datetime );
    if( $datetime_obj > util::get_datetime_object() )
      throw lib::create( 'exception\notice', 'Cannot set future end times.', __METHOD__ );

    // make sure there is no activity between the start and end datetimes
    $activity_mod = lib::create( 'database\modifier' );
    $activity_mod->where( 'datetime', '>=', $start_datetime );
    $activity_mod->where( 'datetime', '<=', $end_datetime );
    $activity_mod->where( 'role_id', '=', $columns['role_id'] );
    $activity_mod->where( 'operation.name', 'NOT IN', array( 'begin_break', 'end_break' ) );
    if( $db_user->get_activity_count( $activity_mod ) )
      throw lib::create( 'exception\notice',
        'Unable to change away time, user has activity between the start and end times.',
        __METHOD__ );
  }
}
?>
