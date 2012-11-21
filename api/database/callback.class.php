<?php
/**
 * callback.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\database;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * callback: record
 */
class callback extends \cenozo\database\record
{
  /**
   * Overrides the parent load method.
   * @author Patrick Emond
   * @access public
   */
  public function load()
  {
    parent::load();

    // callbacks are not to the second, so remove the :00 at the end of the datetime field
    $this->datetime = substr( $this->datetime, 0, -3 );
  }
  
  /**
   * Overrides the parent save method.
   * @author Patrick Emond
   * @access public
   */
  public function save()
  {
    // make sure there is a maximum of 1 unassigned callback
    if( is_null( $this->assignment_id ) )
    {
      $modifier = lib::create( 'database\modifier' );
      $modifier->where( 'participant_id', '=', $this->participant_id );
      $modifier->where( 'assignment_id', '=', NULL );
      if( !is_null( $this->id ) ) $modifier->where( 'id', '!=', $this->id );
      if( 0 < static::count( $modifier ) )
        throw lib::create( 'exception\runtime',
          'Cannot have more than one unassigned callback per participant.', __METHOD__ );
    }

    parent::save();
  }
  
  /**
   * Get the state of the callback as a string:
   *   reached: the callback was met and the participant was reached
   *   not reached: the callback was met but the participant was not reached
   *   upcoming: the callback's date/time has not yet occurred
   *   assignable: the callback is ready to be assigned, but hasn't been
   *   missed: the callback was missed (never assigned) and the call window has passed
   *   incomplete: the callback was assigned but the assignment never closed (an error)
   *   assigned: the callback is currently assigned
   *   in progress: the callback is currently assigned and currently in a call
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return string
   * @access public
   */
  public function get_state( $ignore_assignments = false )
  {
    if( is_null( $this->id ) )
    {
      log::warning( 'Tried to determine state for callback with no id.' );
      return NULL;
    } 
    
    // if the callback's reached column is set, nothing else matters
    if( !is_null( $this->reached ) ) return $this->reached ? 'reached' : 'not reached';

    $db_participant = lib::create( 'database\participant', $this->participant_id );
    $db_site = $db_participant->get_primary_site();

    $status = 'unknown';
    
    // settings are in minutes, time() is in seconds, so multiply by 60
    $setting_manager = lib::create( 'business\setting_manager' );
    $pre_window_time  = 60 * $setting_manager->get_setting(
                              'callback', 'call pre-window', $db_site );
    $now = util::get_datetime_object()->getTimestamp();
    $callback = util::get_datetime_object( $this->datetime )->getTimestamp();

    // get the status of the callback
    $db_assignment = $this->get_assignment();
    if( !$ignore_assignments && !is_null( $db_assignment ) )
    {
      if( !is_null( $db_assignment->end_datetime ) )
      { // assignment closed but callback never completed
        log::crit(
          sprintf( 'Callback %d has assignment which is closed but no status was set.',
                   $this->id ) );
        $status = 'incomplete';
      }
      else // assignment active
      {
        $modifier = lib::create( 'database\modifier' ); 
        $modifier->where( 'end_datetime', '=', NULL );
        $open_phone_calls = $db_assignment->get_phone_call_count( $modifier );
        if( 0 < $open_phone_calls )
        { // assignment currently on call
          $status = "in progress";
        }
        else
        { // not on call
          $status = "assigned";
        }
      }
    }
    else if( $now < $callback - $pre_window_time )
    {
      $status = 'upcoming';
    }
    else
    {
      $status = 'assignable';
    }

    return $status;
  }
}

// define the join to the participant_site table
$participant_site_mod = lib::create( 'database\modifier' );
$participant_site_mod->where(
  'callback.participant_id', '=', 'participant_site.participant_id', false );
callback::customize_join( 'participant_site', $participant_site_mod );
?>
