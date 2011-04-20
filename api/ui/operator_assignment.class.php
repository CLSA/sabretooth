<?php
/**
 * operator_assignment.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui;
use sabretooth\log, sabretooth\util;
use sabretooth\business as bus;
use sabretooth\database as db;
use sabretooth\exception as exc;

/**
 * widget operator assignment
 * 
 * @package sabretooth\ui
 */
class operator_assignment extends widget
{
  /**
   * Constructor
   * 
   * Defines all variables which need to be set for the associated template.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'operator', 'assignment', $args );
    $this->set_heading( 'Current Assignment' );
  }

  /**
   * Finish setting the variables in a widget.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function finish()
  {
    parent::finish();
    
    $session = bus\session::self();

    // see if this user has an open assignment
    $db_assignment = $session->get_current_assignment();

    if( !is_null( $db_assignment ) )
    { // fill out the participant's details
      $db_participant = $db_assignment->get_interview()->get_participant();
      
      $name = sprintf( $db_participant->first_name.' '.$db_participant->last_name );

      $language = 'none';
      if( 'en' == $db_participant->language ) $language = 'english';
      else if( 'fr' == $db_participant->language ) $language = 'french';

      $consent = 'none';
      $db_consent = $db_participant->get_current_consent();
      if( !is_null( $db_consent ) ) $consent = $db_consent->event;
      
      $previous_call_list = array();
      $db_last_assignment = $db_participant->get_last_finished_assignment();
      if( !is_null( $db_last_assignment ) )
      {
        foreach( $db_last_assignment->get_phone_call_list() as $db_phone_call )
        {
          $db_contact = $db_phone_call->get_contact();
          $previous_call_list[] = sprintf( 'Called contact #%d (%s): %s',
            $db_contact->rank,
            $db_contact->type,
            $db_phone_call->status ? $db_phone_call->status : 'unknown' );
        }
      }

      $modifier = new db\modifier();
      $modifier->where( 'active', '=', true );
      $modifier->where( 'phone', '!=', NULL );
      $modifier->order( 'rank' );
      $db_contact_list = $db_participant->get_contact_list( $modifier );
      
      $modifier = new db\modifier();
      $modifier->where( 'end_time', '!=', NULL );
      $current_calls = $db_assignment->get_phone_call_count( $modifier );

      if( 0 == count( $db_contact_list ) )
      {
        log::crit(
          sprintf( 'An operator has been assigned participant %d who has no callable contacts',
          $db_participant->id ) );
      }
      else
      {
        $contact_list = array();
        foreach( $db_contact_list as $db_contact )
          $contact_list[$db_contact->id] =
            sprintf( '%d. %s (%s)', $db_contact->rank, $db_contact->type, $db_contact->phone );
        $this->set_variable( 'contact_list', $contact_list );
        $this->set_variable( 'status_list',
          db\phone_call::get_enum_values( 'status' ) );
      }

      $this->set_variable( 'assignment_id', $db_assignment->id );
      $this->set_variable( 'participant_id', $db_participant->id );
      $this->set_variable( 'participant_note_count', $db_participant->get_note_count() );
      $this->set_variable( 'participant_name', $name );
      $this->set_variable( 'participant_language', $language );
      $this->set_variable( 'participant_consent', $consent );
      if( !is_null( $db_last_assignment ) )
      {
        $this->set_variable( 'previous_assignment_id', $db_last_assignment->id );
        $this->set_variable( 'previous_assignment_note_count',
          $db_last_assignment->get_note_count() );
        $this->set_variable( 'previous_assignment_date',
          util::get_formatted_date( $db_last_assignment->start_time ) );
        $this->set_variable( 'previous_assignment_time',
          util::get_formatted_time( $db_last_assignment->start_time ) );
      }
      $this->set_variable( 'previous_call_list', $previous_call_list );
      $this->set_variable( 'current_calls', $current_calls );
      $this->set_variable( 'allow_call', $session->get_allow_call() );
      $this->set_variable( 'on_call', !is_null( $session->get_current_phone_call() ) );
    }
  }
}
?>
