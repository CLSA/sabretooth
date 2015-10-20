<?php
/**
 * phone_call.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\database;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * phone_call: record
 */
class phone_call extends \cenozo\database\record
{
  /**
   * Overrides the parent save method.
   * @author Patrick Emond
   * @access public
   */
  public function save()
  {
    if( !is_null( $this->assignment_id ) && is_null( $this->end_datetime ) )
    {
      // make sure there is a maximum of 1 unfinished call per assignment
      $modifier = lib::create( 'database\modifier' );
      $modifier->where( 'assignment_id', '=', $this->assignment_id );
      $modifier->where( 'end_datetime', '=', NULL );
      if( 0 < static::count( $modifier ) )
        throw lib::create( 'exception\runtime',
          'Cannot have more than one active phone call per assignment.', __METHOD__ );
    }

    parent::save();
  }

  // TODO: document
  public function process_events()
  {
    if( !is_null( $this->end_datetime ) )
    {
      $db_interview = $this->get_assignment()->get_interview();
      $db_participant = $db_interview->get_participant();
      $db_qnaire = $db_interview->get_qnaire();

      // mark first attempt events
      $event_mod = lib::create( 'database\modifier' );
      $event_mod->where( 'event_type_id', '=', $db_qnaire->first_attempt_event_type_id );
      if( 0 == $db_participant->get_event_count( $event_mod ) ) 
      {   
        $db_event = lib::create( 'database\event' );
        $db_event->participant_id = $db_participant->id;
        $db_event->event_type_id = $db_qnaire->first_attempt_event_type_id;
        $db_event->datetime = $this->start_datetime;
        $db_event->save();
      }   

      // mark reached events
      $event_mod = lib::create( 'database\modifier' );
      $event_mod->where( 'event_type_id', '=', $db_qnaire->reached_event_type_id );
      if( 'contacted' == $this->status && 0 == $db_participant->get_event_count( $event_mod ) ) 
      {   
        $db_event = lib::create( 'database\event' );
        $db_event->participant_id = $db_participant->id;
        $db_event->event_type_id = $db_qnaire->reached_event_type_id;
        $db_event->datetime = $this->start_datetime;
        $db_event->save();
      }   

      // mark any completed script events
      $script_class_name = lib::get_class_name( 'database\script' );
      $script_class_name::add_all_event_types( $db_participant );
    }
  }
}
