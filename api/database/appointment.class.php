<?php
/**
 * appointment.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\database
 * @filesource
 */

namespace sabretooth\database;

/**
 * appointment: record
 *
 * @package sabretooth\database
 */
class appointment extends record
{
  /**
   * Overrides the parent load method.
   * @author Patrick Emond
   * @access public
   */
  public function load()
  {
    parent::load();

    // appointments are not to the second, so remove the :00 at the end of the date field
    $this->date = substr( $this->date, 0, -3 );
  }
  
  /**
   * Get the status of the appointment as a string (upcoming, missed, completed, in progress or
   * assigned)
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return string
   * @access public
   */
  public function get_state()
  {
    if( is_null( $this->id ) )
    {
      \sabretooth\log::warning( 'Tried to determine state for appointment with no id.' );
      return NULL;
    } 
    
    // if the appointment has a status, nothing else matters
    if( !is_null( $this->status ) ) return $this->status;

    $status = 'unknown';
    
    // settings are in minutes, time() is in seconds, so multiply by 60
    $pre_window_time = 60 *
      \sabretooth\database\setting::get_setting( 'appointment', 'call pre-window' )->value;
    $post_window_time = 60 *
      \sabretooth\database\setting::get_setting( 'appointment', 'call post-window' )->value;
    $now = time();
    $appointment = strtotime( $this->date );

    // get the status of the appointment
    if( $now < $appointment - $pre_window_time )
    {
      $status = 'upcoming';
    }
    else if( $now < $appointment + $post_window_time )
    {
      $status = 'assignable';
    }
    else
    { // not in the future
      if( is_null( $this->assignment_id ) )
      { // not assigned
        $status = 'missed';
      }
      else // assigned
      {
        $db_assignment = $this->get_assignment();
        if( !is_null( $db_assignment->end_time ) )
        { // assignment closed but appointment never completed
          \sabretooth\log::crit(
            sprintf( 'Appointment %d has assignment which is closed but no status was set.',
                     $this->id ) );
          $status = 'incomplete';
        }
        else // assignment active
        {
          $modifier = new modifier();
          $modifier->where( 'end_time', '=', NULL );
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
    }

    return $status;
  }
}
?>
