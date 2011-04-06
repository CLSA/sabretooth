<?php
/**
 * appointment_view.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui;

/**
 * widget appointment view
 * 
 * @package sabretooth\ui
 */
class appointment_view extends base_view
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
    parent::__construct( 'appointment', 'view', $args );
    
    // add items to the view
    $this->add_item( 'contact_id', 'enum', 'Phone Number' );
    $this->add_item( 'date', 'datetime', 'Date' );
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
    
    // create enum arrays
    $db_participant = new \sabretooth\database\participant( $this->get_record()->participant_id );
    $modifier = new \sabretooth\database\modifier();
    $modifier->where( 'phone', '!=', NULL );
    $modifier->order( 'rank' );
    $contacts = array();
    foreach( $db_participant->get_contact_list( $modifier ) as $db_contact )
      $contacts[$db_contact->id] = $db_contact->rank.". ".$db_contact->phone;
    
    // set the view's items
    $this->set_item( 'contact_id', $this->get_record()->contact_id, true, $contacts );
    $this->set_item( 'date', $this->get_record()->date, true );

    $this->finish_setting_items();
  }
}
?>
