<?php
/**
 * availability_list.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui;

/**
 * widget availability list
 * 
 * @package sabretooth\ui
 */
class availability_list extends base_list_widget
{
  /**
   * Constructor
   * 
   * Defines all variables required by the availability list.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'availability', $args );
    
    $session = \sabretooth\session::self();

    $this->add_column( 'monday', 'Mon', true );
    $this->add_column( 'tuesday', 'Tue', true );
    $this->add_column( 'wednesday', 'Wed', true );
    $this->add_column( 'thursday', 'Thu', true );
    $this->add_column( 'friday', 'Fri', true );
    $this->add_column( 'saturday', 'Sat', true );
    $this->add_column( 'sunday', 'Sun', true );
    $this->add_column( 'start_time', 'Start', true );
    $this->add_column( 'end_time', 'End', true );
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
      $start_time = \sabretooth\util::get_formatted_time(
        \sabretooth\util::from_server_time( $record->start_time ), false );
      $end_time = \sabretooth\util::get_formatted_time(
        \sabretooth\util::from_server_time( $record->end_time ), false );

      $this->add_row( $record->id,
        array( 'monday' => $record->monday ? 'Yes' : 'No',
               'tuesday' => $record->tuesday ? 'Yes' : 'No',
               'wednesday' => $record->wednesday ? 'Yes' : 'No',
               'thursday' => $record->thursday ? 'Yes' : 'No',
               'friday' => $record->friday ? 'Yes' : 'No',
               'saturday' => $record->saturday ? 'Yes' : 'No',
               'sunday' => $record->sunday ? 'Yes' : 'No',
               'start_time' => $start_time,
               'end_time' => $end_time ) );
    }

    $this->finish_setting_rows();
  }
}
?>
