<?php
/**
 * away_time_edit.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui\push;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * push: away_time edit
 *
 * Edit an away time.
 */
class away_time_edit extends \cenozo\ui\push\base_edit
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

    // get the modified user, start/end datetimes
    $db_user = array_key_exists( 'user_id', $columns )
             ? lib::create( 'database\user', $columns['user_id'] )
             : $this->get_record()->get_user();
    $start_datetime = array_key_exists( 'start_datetime', $columns )
                    ? $columns['start_datetime']
                    : $this->get_record()->start_datetime;
    $end_datetime = array_key_exists( 'end_datetime', $columns )
                  ? $columns['end_datetime']
                  : $this->get_record()->end_datetime;

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
    $activity_mod->where( 'role_id', '=', $this->get_record()->role_id );
    $activity_mod->where( 'operation.name', 'NOT IN', array( 'begin_break', 'end_break' ) );
    if( $db_user->get_activity_count( $activity_mod ) )
      throw lib::create( 'exception\notice',
        'Unable to change away time, user has activity between the start and end times.',
        __METHOD__ );
  }
}
?>
