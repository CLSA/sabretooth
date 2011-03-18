<?php
/**
 * contact_view.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui;

/**
 * widget contact view
 * 
 * @package sabretooth\ui
 */
class contact_view extends base_view
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
    parent::__construct( 'contact', 'view', $args );
    
    // add items to the view
    $this->add_item( 'active', 'boolean', 'Active' );
    $this->add_item( 'rank', 'enum', 'Rank' );
    $this->add_item( 'type', 'enum', 'Type' );
    $this->add_item( 'phone', 'string', 'Phone' );
    $this->add_item( 'address1', 'string', 'Address1' );
    $this->add_item( 'address2', 'string', 'Address2' );
    $this->add_item( 'city', 'string', 'City' );
    $this->add_item( 'province_id', 'enum', 'Province' );
    $this->add_item( 'country', 'string', 'Country' );
    $this->add_item( 'postcode', 'string', 'Postcode' );
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

    // create enum arrays
    $num_contacts = $this->get_record()->get_participant()->get_contact_count();
    $ranks = array();
    for( $rank = 1; $rank <= $num_contacts; $rank++ ) $ranks[] = $rank;
    $ranks = array_combine( $ranks, $ranks );
    $types = \sabretooth\database\contact::get_enum_values( 'type' );
    $types = array_combine( $types, $types );
    $provinces = array();
    foreach( \sabretooth\database\province::select() as $db_province )
      $provinces[$db_province->id] = $db_province->name;

    // set the view's items
    $this->set_item( 'active', $this->get_record()->active, true );
    $this->set_item( 'rank', $this->get_record()->rank, true, $ranks );
    $this->set_item( 'type', $this->get_record()->type, true, $types );
    $this->set_item( 'phone', $this->get_record()->phone );
    $this->set_item( 'address1', $this->get_record()->address1 );
    $this->set_item( 'address2', $this->get_record()->address2 );
    $this->set_item( 'city', $this->get_record()->city );
    $this->set_item( 'province_id', $this->get_record()->province_id, false, $provinces );
    $this->set_item( 'country', $this->get_record()->country );
    $this->set_item( 'postcode', $this->get_record()->postcode );
    $this->set_item( 'note', $this->get_record()->note );

    $this->finish_setting_items();
  }
}
?>
