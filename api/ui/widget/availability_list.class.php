<?php
/**
 * availability_list.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui\widget;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * widget availability list
 */
class availability_list extends \cenozo\ui\widget\base_list
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
    
    $this->add_column( 'monday', 'boolean', 'M', true );
    $this->add_column( 'tuesday', 'boolean', 'T', true );
    $this->add_column( 'wednesday', 'boolean', 'W', true );
    $this->add_column( 'thursday', 'boolean', 'T', true );
    $this->add_column( 'friday', 'boolean', 'F', true );
    $this->add_column( 'saturday', 'boolean', 'S', true );
    $this->add_column( 'sunday', 'boolean', 'S', true );
    $this->add_column( 'start_time', 'time', 'Start', true );
    $this->add_column( 'end_time', 'time', 'End', true );
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
  }
}
?>
