<?php
/**
 * shift.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\database
 * @filesource
 */

namespace sabretooth\database;

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
      \sabretooth\log::warning( 'Tried to save read-only record.' );
      return;
    }
    
    // make sure start time comes after end time
    $start_obj = new \DateTime( $this->start_time );
    $end_obj = new \DateTime( $this->end_time );
    $interval = $start_obj->diff( $end_obj );
    if( 0 != $interval->invert ||
        ( 0 == $interval->days && 0 == $interval->h && 0 == $interval->i && 0 == $interval->s ) )
    {
      throw new \sabretooth\exception\runtime(
        'Tried to set end time which is not after the start time in shift record.', __METHOD__ );
    }

    // convert the start and end times to server time
    $start_time = \sabretooth\util::to_server_time( $this->start_time );
    $end_time = \sabretooth\util::to_server_time( $this->end_time );
    
    // See if the user already has a shift at this time
    $modifier = new modifier();
    $modifier->where( 'id', '!=', $this->id );
    $modifier->where( 'user_id', '=', $this->user_id );
    $modifier->where( 'date', '=', $this->date );
    
    // (need to use custom SQL)
    $count = static::db()->get_one( 
      sprintf( 'SELECT COUNT(*) FROM %s %s '.
               'AND NOT ( ( start_time <= %s AND end_time <= %s ) OR '.
                         '( start_time >= %s AND end_time >= %s ) )',
               static::get_table_name(),
               $modifier->get_where(),
               database::format_string( $start_time ),
               database::format_string( $start_time ),
               database::format_string( $end_time ),
               database::format_string( $end_time ) ) );
    
    if( 0 < $count )
    {
      throw new \sabretooth\exception\runtime(
        'Tried to create shift which overlaps with another shift\'s time.', __METHOD__ );
    }
    
    // all is well, continue with the parent's save method
    parent::save();
  }
}
?>
