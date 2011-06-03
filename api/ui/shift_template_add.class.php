<?php
/**
 * shift_template_add.class.php
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
 * widget shift_template add
 * 
 * @package sabretooth\ui
 */
class shift_template_add extends base_view
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
    parent::__construct( 'shift_template', 'add', $args );
    
    // check for initial values
    $this->date = $this->get_argument( 'date', NULL );
    $this->start_time = $this->get_argument( 'start_time', NULL );
    $this->end_time = $this->get_argument( 'end_time', NULL );

    // add items to the view
    $this->add_item( 'site_id', 'hidden' );
    $this->add_item( 'start_time', 'time', 'Start Time' );
    $this->add_item( 'end_time', 'time', 'End Time' );
    $this->add_item( 'operators', 'number', 'Operators' );
    $this->add_item( 'start_date', 'date', 'Start Date' );
    $this->add_item( 'end_date', 'date', 'End Date' );

    $this->set_heading( 'Creating a new shift template' );
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

    $this->set_variable( 'start_date', $this->date );

    // set the view's items
    $this->set_item( 'site_id', bus\session::self()->get_site()->id, true );
    $this->set_item( 'start_time', $this->start_time, true );
    $this->set_item( 'end_time', $this->end_time, true );
    $this->set_item( 'operators', 1, true );
    $this->set_item( 'start_date', $this->date, true );
    $this->set_item( 'end_date', '', false );
    
    $this->finish_setting_items();
  }

  /**
   * The initial date.
   * @var string
   * @access public
   */
  public $date = '';

  /**
   * The initial start time.
   * @var string
   * @access public
   */
  public $start_time = '';

  /**
   * The initial end time.
   * @var string
   * @access public
   */
  public $end_time = '';
}
?>
