<?php
/**
 * assignment.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
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
   * @param boolean $completed Whether the assignment is being closed
   * @access public
   */
  function post_process( $completed )
  {
    parent::post_process( $completed );

    $db_application = lib::create( 'business\session' )->get_application();
    $db_queue = $this->get_queue();
    $db_interview = $this->get_interview();

    // set the assignment and outcome columns in appointments
    if( $db_queue->from_appointment() )
    {
      // if complete then search for associated appointments, otherwise search for unassociated ones
      $modifier = lib::create( 'database\modifier' );
      $modifier->join( 'vacancy', 'appointment.start_vacancy_id', 'vacancy.id' );
      $modifier->where( 'assignment_id', '=', $completed ? $this->id : NULL );
      if( !$completed )
      {
        // get the pre-call window setting
        $db_site = $db_interview->get_participant()->get_effective_site();
        $pre_call_window = is_null( $db_site ) ? 0 : $db_site->get_setting()->pre_call_window;
        // make sure not to select future appointments
        $modifier->where(
          sprintf( 'vacancy.datetime - INTERVAL %d MINUTE', $pre_call_window ),
          '<=',
          $this->start_datetime->format( 'Y-m-d H:i:s' )
        );
      }

      foreach( $db_interview->get_appointment_object_list( $modifier ) as $db_appointment )
      {
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
