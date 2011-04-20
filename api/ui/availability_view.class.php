<?php
/**
 * availability_view.class.php
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
 * widget availability view
 * 
 * @package sabretooth\ui
 */
class availability_view extends base_view
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
    parent::__construct( 'availability', 'view', $args );
    
    // add items to the view
    $this->add_item( 'monday', 'boolean', 'Monday' );
    $this->add_item( 'tuesday', 'boolean', 'Tuesday' );
    $this->add_item( 'wednesday', 'boolean', 'Wednesday' );
    $this->add_item( 'thursday', 'boolean', 'Thursday' );
    $this->add_item( 'friday', 'boolean', 'Friday' );
    $this->add_item( 'saturday', 'boolean', 'Saturday' );
    $this->add_item( 'sunday', 'boolean', 'Sunday' );
    $this->add_item( 'start_time', 'time', 'Start Time' );
    $this->add_item( 'end_time', 'time', 'End Time' );
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

    // set the view's items
    $this->set_item( 'monday', $this->get_record()->monday, true );
    $this->set_item( 'tuesday', $this->get_record()->tuesday, true );
    $this->set_item( 'wednesday', $this->get_record()->wednesday, true );
    $this->set_item( 'thursday', $this->get_record()->thursday, true );
    $this->set_item( 'friday', $this->get_record()->friday, true );
    $this->set_item( 'saturday', $this->get_record()->saturday, true );
    $this->set_item( 'sunday', $this->get_record()->sunday, true );
    $this->set_item( 'start_time', $this->get_record()->start_time, true );
    $this->set_item( 'end_time', $this->get_record()->end_time, true );

    $this->finish_setting_items();
  }
}
?>
