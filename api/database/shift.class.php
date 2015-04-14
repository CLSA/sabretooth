<?php
/**
 * shift.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\database;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * shift: record
 */
class shift extends \cenozo\database\record
{
  /**
   * Overrides the parent class to prevent doubling shift times.
   * 
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

    $db_user = lib::create( 'database\user', $this->user_id );
    $db_site = lib::create( 'database\site', $this->site_id );
    $role_class_name = lib::get_class_name( 'database\role' );
    $db_role = $role_class_name::get_unique_record( 'name', 'operator' );
    
    // Make sure the user has the operator role at the site
    if( !$db_user->has_access( $db_site, $db_role ) )
    {
      throw lib::create( 'exception\runtime',
        sprintf( 'Cannot assign shift to "%s", user does not have operator access to %s',
                 $db_user->name,
                 $db_site->name ), __METHOD__ );
    }

    // See if the user already has a shift at this time
    $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'id', '!=', $this->id );
    $modifier->where( 'user_id', '=', $this->user_id );
    $modifier->where_bracket( true );
    $modifier->where_bracket( true );
    $modifier->where( 'start_datetime', '>', $this->start_datetime );
    $modifier->or_where( 'end_datetime', '>', $this->start_datetime );
    $modifier->where_bracket( false );
    $modifier->where_bracket( true );
    $modifier->where( 'start_datetime', '<', $this->end_datetime );
    $modifier->or_where( 'end_datetime', '<', $this->end_datetime );
    $modifier->where_bracket( false );
    $modifier->where_bracket( false );
    $modifier->limit( 1 );

    $shift_list = static::select_objects( $modifier );
    
    if( 0 < count( $shift_list ) )
    {
      $db_overlap = current( $shift_list );
      throw lib::create( 'exception\notice',
        sprintf( 'There is already a shift which exists for this operator during the requested '.
                 'time (%s to %s).  Please adjust the shift times so that there is no overlap.',
                 $db_overlap->start_datetime->format( 'H:i' ),
                 $db_overlap->end_datetime->format( 'H:i' ) ),
        __METHOD__ );
    }
    
    // all is well, continue with the parent's save method
    parent::save();
  }
}
