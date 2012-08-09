<?php
/**
 * consent_view.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui\widget;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * widget consent view
 */
class consent_view extends \cenozo\ui\widget\base_view
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
    parent::__construct( 'consent', 'view', $args );
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
    
    // add items to the view
    $this->add_item( 'event', 'enum', 'Event' );
    $this->add_item( 'date', 'date', 'Date' );
    $this->add_item( 'note', 'text', 'Note' );
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

    // create enum arrays
    $class_name = lib::get_class_name( 'database\consent' );
    $events = $class_name::get_enum_values( 'event' );
    $events = array_combine( $events, $events );

    // set the view's items
    $this->set_item( 'event', $this->get_record()->event, true, $events );
    $this->set_item( 'date', $this->get_record()->date, true );
    $this->set_item( 'note', $this->get_record()->note );
  }
}
?>
