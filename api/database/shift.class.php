<?php
/**
 * shift.class.php
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
 * shift: record
 *
 * @package sabretooth\database
 */
class shift extends record
{
  /**
   * Overrides the parent class to prevent doubling shift times.
   * 
   * If the record has a stage which already exists it will push the current phase and all that
   * come after it down by one stage to make room for this one.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @throws exception\runtime
   * @access public
   */
  public function save()
  {
    // warn if we are in read-only mode
    if( $this->read_only )
    {
      log::warning( 'Tried to save read-only record.' );
      return;
    }

    $db_user = new user( $this->user_id );
    $db_site = new site( $this->site_id );
    $db_role = role::get_unique_record( 'name', 'operator' );
    
    // Make sure the user has the operator role at the site
    if( !$db_user->has_access( $db_site, $db_role ) )
    {
      throw new exc\runtime(
        sprintf( 'Cannot assign shift to "%s", user does not have operator access to %s',
                 $db_user->name,
                 $db_site->name ), __METHOD__ );
    }

    // See if the user already has a shift at this time
    $modifier = new modifier();
    $modifier->where( 'id', '!=', $this->id );
    $modifier->where( 'user_id', '=', $this->user_id );
    $modifier->where( 'date', '=', $this->date );
    
    // convert the start and end times to server time
    $start_time = util::to_server_datetime( $this->start_time, 'H:i:s' );
    $end_time = util::to_server_datetime( $this->end_time, 'H:i:s' );

    // (need to use custom SQL)
    $overlap_ids = static::db()->get_col( 
      sprintf( 'SELECT id FROM %s %s '.
               'AND NOT ( ( start_time <= %s AND end_time <= %s ) OR '.
                         '( start_time >= %s AND end_time >= %s ) )',
               static::get_table_name(),
               $modifier->get_where(),
               database::format_string( $start_time ),
               database::format_string( $start_time ),
               database::format_string( $end_time ),
               database::format_string( $end_time ) ) );
    
    if( 0 < count( $overlap_ids ) )
    {
      $overlap_id = current( $overlap_ids );
      $db_overlap = new static( $overlap_id );
      throw new exc\runtime(
        sprintf( 'Shift time (%s to %s) for user "%s" overlaps '.
                 'with another shift on the same day (%s to %s).',
                 $this->start_time,
                 $this->end_time,
                 $db_user->name,
                 substr( $db_overlap->start_time, 0, -3 ),
                 substr( $db_overlap->end_time, 0, -3 ) ),
        __METHOD__ );
    }
    
    // all is well, continue with the parent's save method
    parent::save();
  }
}
?>
