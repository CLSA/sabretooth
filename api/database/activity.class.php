<?php
/**
 * activity.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\database
 * @filesource
 */

namespace sabretooth\database;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * activity: record
 *
 * @package sabretooth\database
 */
class activity extends \cenozo\database\activity
{
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

  //TODO check why the cenozo activity class does not have remove away time
  public static function get_elapsed_time(
    $db_user, $db_site, $db_role, $date, $remove_away_time = true )
  {
    $total_time = parent::get_elapsed_time( $db_user, $db_site, $db_role, $date );

    //TODO: check about the diff with cenozo

    // now substract all away times, if necessary
    if( $remove_away_time )
    {
      $away_time_mod = lib::create( 'database\modifier' );
      $away_time_mod->where( 'user_id', '=', $db_user->id );
      $away_time_mod->where( 'start_datetime', '>=', $date.' 0:00:00' );
      $away_time_mod->where( 'start_datetime', '<=', $date.' 23:59:59' );
      $away_time_mod->where( 'end_datetime', '>=', $date.' 0:00:00' );
      $away_time_mod->where( 'end_datetime', '<=', $date.' 23:59:59' );

      // TODO since away_time.class.php is only in sabretooth and not in cenozo
      // do we need to get the class name for this static select method usage?
      $class_name = lib::get_class_name( 'database\away_time' );
      foreach( $class_name::select( $away_time_mod ) as $db_away_time )
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
