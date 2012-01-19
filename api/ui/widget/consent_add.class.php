<?php
/**
 * consent_add.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui\widget;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * widget consent add
 * 
 * @package sabretooth\ui
 */
class consent_add extends \cenozo\ui\widget\base_view
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
    parent::__construct( 'consent', 'add', $args );
    
    // add items to the view
    $this->add_item( 'participant_id', 'hidden' );
    $this->add_item( 'event', 'enum', 'Event' );
    $this->add_item( 'date', 'date', 'Date' );
    $this->add_item( 'note', 'text', 'Note' );
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
    
    // this widget must have a parent, and it's subject must be a participant
    if( is_null( $this->parent ) || 'participant' != $this->parent->get_subject() )
      throw lib::create( 'exception\runtime',
        'Consent widget must have a parent with participant as the subject.', __METHOD__ );
    
    // create enum arrays
    $class_name = lib::get_class_name( 'database\consent' );
    $events = $class_name::get_enum_values( 'event' );
    $events = array_combine( $events, $events );

    // set the view's items
    $this->set_item( 'participant_id', $this->parent->get_record()->id );
    $this->set_item( 'event', key( $events ), true, $events );
    $this->set_item( 'date', '', true );
    $this->set_item( 'note', '' );

    $this->finish_setting_items();
  }
}
?>
