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
      // if complete then search for associated appointments, otherwise search for unassociated ones
      $modifier = lib::create( 'database\modifier' );
      $modifier->where( 'assignment_id', '=', $completed ? $this->id : NULL );

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
