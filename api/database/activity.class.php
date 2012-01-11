<?php
/**
 * activity.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\database
 * @filesource
 */

namespace sabretooth\database;
use sabretooth\log, sabretooth\util;
use sabretooth\business as bus;
use sabretooth\exception as exc;

/**
 * activity: record
 *
 * @package sabretooth\database
 */
class activity extends record
{
  /**
   * Get the datetime of the earliest/first activity.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param modifier $modifier Modification to the query.
   * @return \DateTime
   * @static
   * @access public
   */
  public static function get_min_datetime( $modifier = NULL )
  {
    $sql = sprintf( 'SELECT MIN( datetime ) FROM %s '.
                    'LEFT JOIN operation ON operation_id = operation.id %s',
                    static::get_table_name(),
                    is_null( $modifier ) ? '' : $modifier->get_sql() );
    $datetime = static::db()->get_one( $sql );
    
    return is_null( $datetime )
      ? NULL
      : util::get_datetime_object( util::from_server_datetime( $datetime ) );
  }

  /**
   * Get the datetime of the latest/last activity.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param modifier $modifier Modification to the query.
   * @return \DateTime
   * @static
   * @access public
   */
  public static function get_max_datetime( $modifier = NULL )
  {
    $sql = sprintf( 'SELECT MAX( datetime ) FROM %s '.
                    'LEFT JOIN operation ON operation_id = operation.id %s',
                    static::get_table_name(),
                    is_null( $modifier ) ? '' : $modifier->get_sql() );

    $datetime = static::db()->get_one( $sql );

    return is_null( $datetime )
      ? NULL
      : util::get_datetime_object( util::from_server_datetime( $datetime ) );
  }

  /**
   * Returns the number of hours that a user has spend at a given site and role on a
   * particular day.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param user $db_user The user to query.
   * @param site $db_site The site to query.
   * @param role $db_role The role to query.
   * @param string $date A date string in any valid PHP date time format.
   * @param boolean $remove_away_time Whether to remove away time from the total.
   * @return float
   * @static
   * @access public
   */
  public static function get_elapsed_time(
    $db_user, $db_site, $db_role, $date, $remove_away_time = true )
  {
    $time = 0;
    $total_time = 0;
    $start_datetime_obj = NULL;
    $end_datetime_obj = NULL;

    $activity_mod = new modifier();
    $activity_mod->where( 'user_id', '=', $db_user->id );
    $activity_mod->where( 'site_id', '=', $db_site->id );
    $activity_mod->where( 'operation.subject', '!=', 'self' );
    $activity_mod->where( 'datetime', '>=', $date.' 0:00:00' );
    $activity_mod->where( 'datetime', '<=', $date.' 23:59:59' );

    foreach( static::select( $activity_mod ) as $db_activity )
    {
      if( $db_activity->role_id == $db_role->id )
      {
        if( is_null( $start_datetime_obj ) )
        {
          $start_datetime_obj = util::get_datetime_object( $db_activity->datetime );
          $time = 0;
        }
        else
        {
          $end_datetime_obj = util::get_datetime_object( $db_activity->datetime );
          $interval_obj = util::get_interval( $end_datetime_obj, $start_datetime_obj );
          $time = $interval_obj->h + $interval_obj->i / 60 + $interval_obj->s / 3600;
        }
      }
      else // the user changed role, stop counting time
      {
        $total_time += $time;
        $start_datetime_obj = NULL;
        $time = 0;
      }
    }

    $total_time += $time;

   //TODO: check about the diff with cenozo

    // now substract all away times, if necessary
    if( $remove_away_time )
    {
      $away_time_mod = new modifier();
      $away_time_mod->where( 'user_id', '=', $db_user->id );
      $away_time_mod->where( 'start_datetime', '>=', $date.' 0:00:00' );
      $away_time_mod->where( 'start_datetime', '<=', $date.' 23:59:59' );
      $away_time_mod->where( 'end_datetime', '>=', $date.' 0:00:00' );
      $away_time_mod->where( 'end_datetime', '<=', $date.' 23:59:59' );
      foreach( away_time::select( $away_time_mod ) as $db_away_time )
      {
        if( $db_away_time->end_datetime && $db_away_time->start_datetime )
        {
          $interval_obj =
            util::get_interval( $db_away_time->end_datetime, $db_away_time->start_datetime );
          $time = $interval_obj->h + $interval_obj->i / 60 + $interval_obj->s / 3600;
          $total_time -= $time;
        }
      }
    }

    return $total_time;
  }
}
?>
