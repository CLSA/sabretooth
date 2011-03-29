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
  public function get_status()
  {
    $status = 'unknown';

    // get the status of the appointment
    if( strtotime( $this->date ) > time() )
    {
      $status = 'upcoming';
    }
    else
    { // not in the future
      $db_assignment = $this->get_assignment();
      if( is_null( $db_assignment ) )
      { // not assigned
        $status = 'missed';
      }
      else // assigned
      {
        if( !is_null( $db_assignment->end_time ) )
        { // assignment closed
          $status = 'completed';
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

  /**
   * Returns the assignment associated with this appointment.  If this appointment has not been
   * assigned then NULL is returned.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return assignment
   * @access public
   */
  public function get_assignment()
  {
    if( is_null( $this->id ) )
    {
      \sabretooth\log::warning( 'Tried to determine access for user with no id.' );
      return NULL;
    } 
    
    // requires custom SQL
    $id = static::db()->get_one(
      sprintf( "SELECT assignment.id
                FROM appointment, interview, assignment, queue
                WHERE appointment.id = %s
                AND appointment.participant_id = interview.participant_id
                AND interview.id = assignment.interview_id
                AND assignment.queue_id = queue.id
                AND ( queue.name = 'Missed' OR queue.name = 'Appointments' )",
                $this->id ) );
    
    return is_null( $id ) ? NULL : new assignment( $id );
  }
}
?>
