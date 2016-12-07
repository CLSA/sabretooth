<?php
/**
 * assignment.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\database;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * assignment: record
 */
class assignment extends \cenozo\database\assignment
{
  /**
   * Overrides the parent save method.
   */
  public function save()
  {
    parent::save();
    $this->get_interview()->get_participant()->repopulate_queue( true );
  }

  /**
   * Override the parent method
   */
  public function delete()
  {
    $db_participant = $this->get_interview()->get_participant();
    parent::delete();
    $db_participant->repopulate_queue( true );
  }

  /**
   * Processes changes to appointments and callbacks based on this assignment
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param boolean $completed Whether the assignment is being closed
   * @access public
   */
  function post_process( $completed )
  {
    parent::post_process( $completed );

    $db_application = lib::create( 'business\session' )->get_application();
    $db_queue = $this->get_queue();
    $db_interview = $this->get_interview();
    $db_participant = $db_interview->get_participant();

    // set the assignment and outcome columns in appointments
    if( $db_queue->from_appointment() )
    {
      $modifier2 = NULL;
      $modifier = lib::create( 'database\modifier' );
      $modifier->order( 'datetime' );
      $modifier->limit( 1 );

      // if the assignment is complete then the appointment is already associated with it
      if( $completed ) $modifier->where( 'assignment_id', '=', $this->id );
      // if the assignment is not complete then we have to find the unassigned appointment
      else
      {
        // get the pre-call window setting
        $db_site = $db_participant->get_effective_site();
        $pre_call_window = is_null( $db_site ) ? 0 : $db_site->get_setting()->pre_call_window;

        // make sure not to select future appointments
        $modifier->where(
          sprintf( 'appointment.datetime - INTERVAL %d MINUTE', $pre_call_window ),
          '<=',
          $this->start_datetime->format( 'Y-m-d H:i:s' )
        );

        // make a copy of the modifier to use below, if needed
        $modifier2 = clone $modifier;

        // only select unassigned appointments
        $modifier->where( 'assignment_id', '=', NULL );
      }


      $appointment_list = $db_interview->get_appointment_object_list( $modifier );
      $db_appointment = NULL;
      if( count( $appointment_list ) ) $db_appointment = current( $appointment_list );
      else if( !is_null( $modifier2 ) )
      {
        // no appointment found, check to see if there is an appointment in a "broken" state
        // Broken appointments are assigned to completed assignements but have no outcome
        $modifier2->order_desc( 'datetime' );
        $appointment_list = $db_interview->get_appointment_object_list( $modifier2 );
        if( count( $appointment_list ) )
        {
          $db_possible_appointment = current( $appointment_list );
          if( is_null( $db_possible_appointment->outcome ) )
          {
            $db_old_assignment = $db_possible_appointment->get_assignment();
            // use this appointment if it is already assigned to this assignment, or the assignment
            // it is assigned to has ended (since the appointment has no outcome)
            if( $db_old_assignment->id == $this->id || !is_null( $db_old_assignment->end_datetime ) )
              $db_appointment = $db_possible_appointment;
          }
        }
      }

      if( !is_null( $db_appointment ) )
      {
        $db_appointment = current( $appointment_list );

        // if the assignment is complete then set the appointment's outcome property
        if( $completed )
        {
          $modifier = lib::create( 'database\modifier' );
          $modifier->where( 'status', '=', 'contacted' );
          $db_appointment->outcome = 0 < $this->get_phone_call_count( $modifier ) ? 'reached' : 'not reached';
        }
        // if the assignment is not complete then just set the appointment's assignment
        else $db_appointment->assignment_id = $this->id;
        $db_appointment->save();
      }
    }
  }
}
