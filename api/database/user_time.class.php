<?php
/**
 * user_time.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\database
 * @filesource
 */

namespace sabretooth\database;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * user_time: record
 *
 * @package sabretooth\database
 */
class user_time extends \cenozo\database\record
{
  /**
   * Returns the total number of hours of activity for a particular user, role and site for the
   * given dates.  This table is a cache of information from the activity table, so this method
   * writes entries to the table when they are missing.  Afterwards they are read directly from
   * the table to save processing time.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\user $db_user
   * @param database\role $db_role
   * @param database\site $db_site
   * @param \Datetime $start_datetime_obj Set to NULL to not restrict by start time.
   * @param \Datetime $end_datetime_obj Set to NULL to not resstrict by end time.
   * @param boolean $round_times Whether to round single-day times to 15 minute intervals
   * @return database\record
   * @access public
   * @static
   */
  public static function get_sum(
    $db_user, $db_site, $db_role,
    $start_datetime_obj = NULL, $end_datetime_obj = NULL,
    $round_times = false )
  {
    $activity_class_name = lib::get_class_name( 'database\activity' );

    // constrain the start and end dates from the first activity to now
    $now_datetime_obj = util::get_datetime_object();
    $min_datetime_obj = $activity_class_name::get_min_datetime();
    if( is_null( $start_datetime_obj ) || $start_datetime_obj < $min_datetime_obj )
      $start_datetime_obj = $min_datetime_obj;
    if( is_null( $end_datetime_obj ) || $end_datetime_obj > $now_datetime_obj )
      $end_datetime_obj = $now_datetime_obj;

    // remove time details from the datetime objects
    $start_datetime_obj->setTime( 0, 0, 0 );
    $end_datetime_obj->setTime( 0, 0, 0 );

    // see if any records are missing in the datespan
    $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'user_id', '=', $db_user->id );
    $modifier->where( 'site_id', '=', $db_site->id );
    $modifier->where( 'role_id', '=', $db_role->id );
    $modifier->where( 'date', '>=', $start_datetime_obj->format( 'Y-m-d' ) );
    $modifier->where( 'date', '<=', $end_datetime_obj->format( 'Y-m-d' ) );
    $modifier->order( 'date' );

    // see if we have any missing records and fill them in
    $total_days = util::get_interval( $end_datetime_obj, $start_datetime_obj )->d + 1;
    if( static::count( $modifier ) != $total_days )
    {
      // get a list of dates that already exist
      $existing_dates = static::db()->get_col( sprintf(
        'SELECT date FROM %s %s', static::get_table_name(), $modifier->get_sql() ) );

      $interval = new \DateInterval( 'P1D' );
      for( $datetime_obj = clone $start_datetime_obj;
           $datetime_obj <= $end_datetime_obj;
           $datetime_obj->add( $interval ) )
      {
        $date = $datetime_obj->format( 'Y-m-d' );
        if( $date == current( $existing_dates ) )
        {
          // advance to the next existing date
          next( $existing_dates );
        }
        else
        {
          // calculate the missing time (this is the process-intensive time we're caching)
          $time = $activity_class_name::get_elapsed_time( $db_user, $db_site, $db_role, $date );

          // create the missing entry
          $record = new static();
          $record->user_id = $db_user->id;
          $record->site_id = $db_site->id;
          $record->role_id = $db_role->id;
          $record->date = $date;
          $record->time = $time;
          $record->save();
        }
      }
    }

    // finally, get the sum of all times in the datespan
    return static::db()->get_one( sprintf(
      'SELECT SUM( %s ) FROM %s %s',
      $round_times ? 'FLOOR( 4 * time ) / 4' : 'time',
      static::get_table_name(),
      $modifier->get_sql() ) );
  }
}
?>
