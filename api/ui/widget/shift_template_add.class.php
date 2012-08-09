<?php
/**
 * shift_template_add.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui\widget;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * widget shift_template add
 */
class shift_template_add extends \cenozo\ui\widget\base_view
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
   * @access protected
   */
  protected function setup()
  {
    parent::setup();

    $this->set_variable( 'start_date', $this->date );

    // set the view's items
    $this->set_item( 'site_id', lib::create( 'business\session' )->get_site()->id, true );
    $this->set_item( 'start_time', $this->start_time, true );
    $this->set_item( 'end_time', $this->end_time, true );
    $this->set_item( 'operators', 1, true );
    $this->set_item( 'start_date', $this->date, true );
    $this->set_item( 'end_date', '', false );
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
