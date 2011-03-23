<?php
/**
 * phone_call_list.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui;

/**
 * widget phone_call list
 * 
 * @package sabretooth\ui
 */
class phone_call_list extends base_list_widget
{
  /**
   * Constructor
   * 
   * Defines all variables required by the phone_call list.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'phone_call', $args );
    
    $this->add_column( 'contact.type', 'Contact', true );
    $this->add_column( 'appointment_id', 'Appointment', true );
    $this->add_column( 'start_time', 'Start Time', true );
    $this->add_column( 'end_time', 'End Time', true );
    $this->add_column( 'status', 'Status', true );
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
    
    $start_time = \sabretooth\util::get_formatted_datetime( $record->start_time );
    $end_time = $record->end_time
              ? \sabretooth\util::get_formatted_time( $record->end_time )
              : '(in progress)';

    foreach( $this->get_record_list() as $record )
    {
      // assemble the row for this record
      $this->add_row( $record->id,
        array( 'contact.type' => $record->get_contact()->type,
               'appointment_id' => $record->appointment_id ? 'Yes' : 'No',
               'start_time' => $start_time,
               'end_time' => $end_time,
               'status' => $record->status ) );
    }

    $this->finish_setting_rows();
  }
}
?>
