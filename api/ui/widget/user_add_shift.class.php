<?php
/**
 * user_add_shift.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui\widget;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * widget user add_shift
 * 
 * @package sabretooth\ui
 */
class user_add_shift extends \cenozo\ui\widget\base_add_record
{
  /**
   * Constructor
   * 
   * Defines all variables which need to be set for the associated template.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $name The name of the shift.
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'user', 'shift', $args );
    $this->add_widget->set_heading( "Create a new shift" );

    // set the child widget's properties, if they exist
    $date = $this->get_argument( 'date', NULL );
    if( !is_null( $date ) ) $this->add_widget->date = $date;

    $start_time = $this->get_argument( 'start_time', NULL );
    if( !is_null( $start_time ) ) $this->add_widget->start_time = $start_time;

    $end_time = $this->get_argument( 'end_time', NULL );
    if( !is_null( $end_time ) ) $this->add_widget->end_time = $end_time;
  }
}
?>
