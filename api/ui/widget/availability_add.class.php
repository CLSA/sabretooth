<?php
/**
 * availability_add.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui\widget;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * widget availability add
 */
class availability_add extends \cenozo\ui\widget\base_view
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
    parent::__construct( 'availability', 'add', $args );
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
    $this->add_item( 'participant_id', 'hidden' );
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
   * @access protected
   */
  protected function setup()
  {
    parent::setup();
    
    // this widget must have a parent, and it's subject must be a participant
    if( is_null( $this->parent ) || 'participant' != $this->parent->get_subject() )
      throw lib::create( 'exception\runtime',
        'Consent widget must have a parent with participant as the subject.', __METHOD__ );
    
    // set the view's items
    $this->set_item( 'participant_id', $this->parent->get_record()->id );
    $this->set_item( 'monday', false, true );
    $this->set_item( 'tuesday', false, true );
    $this->set_item( 'wednesday', false, true );
    $this->set_item( 'thursday', false, true );
    $this->set_item( 'friday', false, true );
    $this->set_item( 'saturday', false, true );
    $this->set_item( 'sunday', false, true );
    $this->set_item( 'start_time', '', true );
    $this->set_item( 'end_time', '', true );
  }
}
?>
