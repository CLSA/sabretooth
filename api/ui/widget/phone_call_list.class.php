<?php
/**
 * phone_call_list.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui\widget;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * widget phone_call list
 */
class phone_call_list extends \cenozo\ui\widget\base_list
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
  }

  /**
   * Processes arguments, preparing them for the operation.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @throws exception\notice
   * @access protected
   */
  protected function prepare()
  {
    parent::prepare();
    
    $this->add_column( 'phone.type', 'string', 'Phone', true );
    $this->add_column( 'start_datetime', 'date', 'Date', true );
    $this->add_column( 'start_time', 'time', 'Start Time', false );
    $this->add_column( 'end_time', 'time', 'End Time', false );
    $this->add_column( 'status', 'string', 'Status', true );
  }
  
  /**
   * Set the rows array needed by the template.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access protected
   */
  protected function setup()
  {
    parent::setup();
    
    foreach( $this->get_record_list() as $record )
    {
      // assemble the row for this record
      $this->add_row( $record->id,
        array( 'phone.type' => $record->get_phone()->type,
               'start_datetime' => $record->start_datetime,
               'start_time' => $record->start_datetime,
               'end_time' => $record->end_datetime,
               'status' => $record->status ? $record->status : 'in progress' ) );
    }
  }
}
?>
