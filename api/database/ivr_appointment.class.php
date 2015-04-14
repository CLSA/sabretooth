<?php
/**
 * ivr_appointment.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\database;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * ivr_appointment: record
 */
class ivr_appointment extends \cenozo\database\record
{
  /**
   * Overrides the parent save method.
   * @author Patrick Emond
   * @access public
   */
  public function save()
  {
    // make sure there is a maximum of 1 IVR appointment without a completed status per interview
    if( is_null( $this->completed ) )
    {
      $modifier = lib::create( 'database\modifier' );
      $modifier->where( 'interview_id', '=', $this->interview_id );
      $modifier->where( 'completed', '=', NULL );
      if( !is_null( $this->id ) ) $modifier->where( 'id', '!=', $this->id );
      if( 0 < static::count( $modifier ) )
        throw lib::create( 'exception\runtime',
          'Cannot have more than one incomplete IVR appointment per interview.', __METHOD__ );
    }

    parent::save();
  }
  
  /**
   * Get the state of the IVR appointment as a string:
   *   complete: the IVR appointment is done (the interview is not necessarily complete)
   *   upcoming: the IVR appointment hasn't yet been performed
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return string
   * @access public
   */
  public function get_state()
  {
    if( is_null( $this->id ) )
    {
      log::warning( 'Tried to determine state for ivr_appointment with no id.' );
      return NULL;
    } 
    
    return is_null( $this->completed ) ?  'upcoming' : 'complete';
  }
}
