<?php
/**
 * consent_add.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui;

/**
 * widget consent add
 * 
 * @package sabretooth\ui
 */
class consent_add extends base_view
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
    
    // this widget must have a parent, and it must be a participant
    if( is_null( $this->parent ) ||
        'sabretooth\\ui\\participant_add_consent' != get_class( $this->parent ) )
      throw new \sabretooth\exception\runtime(
        'Consent widget must have participant_view as a parent.', __METHOD );
    
    // create enum arrays
    $events = \sabretooth\database\consent::get_enum_values( 'event' );
    $events = array_combine( $events, $events );

    // set the view's items
    $this->set_item( 'participant_id', $this->parent->get_record()->id );
    $this->set_item( 'event', key( $events ), $events, true );
    $this->set_item( 'date', 'TODO', true );

    $this->finish_setting_items();
  }
}
?>
