<?php
/**
 * availability_list.class.php
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
    
    $this->add_column( 'monday', 'boolean', 'Mon', true );
    $this->add_column( 'tuesday', 'boolean', 'Tue', true );
    $this->add_column( 'wednesday', 'boolean', 'Wed', true );
    $this->add_column( 'thursday', 'boolean', 'Thu', true );
    $this->add_column( 'friday', 'boolean', 'Fri', true );
    $this->add_column( 'saturday', 'boolean', 'Sat', true );
    $this->add_column( 'sunday', 'boolean', 'Sun', true );
    $this->add_column( 'start_time', 'time', 'Start', true );
    $this->add_column( 'end_time', 'time', 'End', true );
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
      $this->add_row( $record->id,
        array( 'monday' => $record->monday,
               'tuesday' => $record->tuesday,
               'wednesday' => $record->wednesday,
               'thursday' => $record->thursday,
               'friday' => $record->friday,
               'saturday' => $record->saturday,
               'sunday' => $record->sunday,
               'start_time' => $record->start_time,
               'end_time' => $record->end_time ) );
    }

    $this->finish_setting_rows();
  }
}
?>
