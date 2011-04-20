<?php
/**
 * assignment_list.class.php
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
 * widget assignment list
 * 
 * @package sabretooth\ui
 */
class assignment_list extends site_restricted_list
{
  /**
   * Constructor
   * 
   * Defines all variables required by the assignment list.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'assignment', $args );
    
    $this->add_column( 'user.name', 'string', 'Operator', true );
    $this->add_column( 'site.name', 'string', 'Site', true );
    $this->add_column( 'participant', 'string', 'Participant', false );
    $this->add_column( 'calls', 'number', 'Calls', false );
    $this->add_column( 'date', 'date', 'Date', true );
    $this->add_column( 'start_time', 'time', 'Start Time', false );
    $this->add_column( 'end_time', 'time', 'End Time', false );
  }
  
  /**
   * Set the rows array needed by the template.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function finish()
  {
    parent::finish();
    
    foreach( $this->get_record_list() as $record )
    {
      $db_participant = $record->get_interview()->get_participant();
      $participant = sprintf( '%s, %s', $db_participant->last_name, $db_participant->first_name );

      // assemble the row for this record
      $this->add_row( $record->id,
        array( 'user.name' => $record->get_user()->name,
               'site.name' => $record->get_site()->name,
               'participant' => $participant,
               'calls' => $record->get_phone_call_count(),
               'date' => $record->start_time,
               'start_time' => $record->start_time,
               'end_time' => $record->end_time,
               // note_count isn't a column, it's used for the note button
               'note_count' => $record->get_note_count() ) );
    }

    $this->finish_setting_rows();
  }

  /**
   * Overrides the parent class method since the record count depends on the active role
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the list.
   * @return int
   * @access protected
   */
  protected function determine_record_count( $modifier = NULL )
  {
    $session = bus\session::self();
    if( 'operator' == $session->get_role()->name )
    {
      if( is_null( $modifier ) ) $modifier = new db\modifier();
      $db_assignment = $session->get_current_assignment();
      $participant_id = is_null( $db_assignment )
                      ? 0
                      : $db_assignment->get_interview()->participant_id;
      $modifier->where( 'interview.participant_id', '=', $participant_id );
      $modifier->where( 'end_time', '!=', NULL );
    }

    return parent::determine_record_count( $modifier );
  }

  /**
   * Overrides the parent class method since the record list depends on the active role.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the list.
   * @return array( record )
   * @access protected
   */
  protected function determine_record_list( $modifier = NULL )
  {
    $session = bus\session::self();
    if( 'operator' == $session->get_role()->name )
    {
      if( is_null( $modifier ) ) $modifier = new db\modifier();
      $db_assignment = $session->get_current_assignment();
      $participant_id = is_null( $db_assignment )
                      ? 0
                      : $db_assignment->get_interview()->participant_id;
      $modifier->where( 'interview.participant_id', '=', $participant_id );
      $modifier->where( 'end_time', '!=', NULL );
    }

    return parent::determine_record_list( $modifier );
  }
}
?>
