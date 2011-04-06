<?php
/**
 * assignment_list.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui;

/**
 * widget assignment list
 * 
 * @package sabretooth\ui
 */
class assignment_list extends base_list_widget
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
    $this->add_column( 'queue.name', 'string', 'Queue', true );
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
               'queue.name' => $record->get_queue()->name,
               'calls' => $record->get_phone_call_count(),
               'date' => $record->start_time,
               'start_time' => $record->start_time,
               'end_time' => $record->end_time ) );
    }

    $this->finish_setting_rows();
  }
}
?>
