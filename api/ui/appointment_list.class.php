<?php
/**
 * appointment_list.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui;

/**
 * widget appointment list
 * 
 * @package sabretooth\ui
 */
class appointment_list extends base_list_widget
{
  /**
   * Constructor
   * 
   * Defines all variables required by the appointment list.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'appointment', $args );
    
    $this->add_column( 'date', 'Date', true );
    $this->add_column( 'status', 'Status', false );
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
      // get the status of the appointment
      if( strtotime( $record->date ) > time() )
      {
        $status = 'upcoming';
      }
      else
      { // not in the future
        $db_assignment = $record->get_assignment();
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
            $modifier = new \sabretooth\database\modifier();
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

      $this->add_row( $record->id,
        array( 'date' => $record->date,
               'status' => $status ) );
    }

    $this->finish_setting_rows();
  }
}
?>
