<?php
/**
 * appointment_add.class.php
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
 * widget appointment add
 * 
 * @package sabretooth\ui
 */
class appointment_add extends base_appointment_view
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
    parent::__construct( 'add', $args );
    
    // add items to the view
    $this->add_item( 'participant_id', 'hidden' );
    $this->add_item( 'contact_id', 'enum', 'Phone Number' );
    $this->add_item( 'datetime', 'datetime', 'Date' );
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
      throw new exc\runtime(
        'Appointment widget must have a parent with participant as the subject.', __METHOD__ );
    
    // create enum arrays
    $db_participant = new db\participant( $this->parent->get_record()->id );
    $modifier = new db\modifier();
    $modifier->where( 'phone', '!=', NULL );
    $modifier->order( 'rank' );
    $contacts = array();
    foreach( $db_participant->get_contact_list( $modifier ) as $db_contact )
      $contacts[$db_contact->id] = $db_contact->rank.". ".$db_contact->phone;

    // set the view's items
    $this->set_item( 'participant_id', $this->parent->get_record()->id );
    $this->set_item( 'contact_id', '', true, $contacts );
    $this->set_item( 'datetime', '', true );

    $this->finish_setting_items();
  }
}
?>
