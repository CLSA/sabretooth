<?php
/**
 * contact_add.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui;

/**
 * widget contact add
 * 
 * @package sabretooth\ui
 */
class contact_add extends base_view
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
    parent::__construct( 'contact', 'add', $args );
    
    // add items to the view
    $this->add_item( 'participant_id', 'hidden' );
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
    
    // this widget must have a parent, and it's subject must be a participant
    if( is_null( $this->parent ) || 'participant' != $this->parent->get_subject() )
      throw new \sabretooth\exception\runtime(
        'Contact widget must have a parent with participant as the subject.', __METHOD__ );
    
    // create enum arrays
    $num_contacts = $this->parent->get_record()->get_contact_count();
    $ranks = array();
    for( $rank = 1; $rank <= ( $num_contacts + 1 ); $rank++ ) $ranks[] = $rank;
    $ranks = array_combine( $ranks, $ranks );
    end( $ranks );
    $last_rank_key = key( $ranks );
    reset( $ranks );
    $types = \sabretooth\database\contact::get_enum_values( 'type' );
    $types = array_combine( $types, $types );
    $provinces = array();
    foreach( \sabretooth\database\province::select() as $db_province )
      $provinces[$db_province->id] = $db_province->name;

    // set the view's items
    $this->set_item( 'participant_id', $this->parent->get_record()->id );
    $this->set_item( 'active', true, true );
    $this->set_item( 'rank', $last_rank_key, true, $ranks );
    $this->set_item( 'type', key( $types ), true, $types );
    $this->set_item( 'phone', '' );
    $this->set_item( 'address1', '' );
    $this->set_item( 'address2', '' );
    $this->set_item( 'city', '' );
    $this->set_item( 'province_id', '', false, $provinces );
    $this->set_item( 'country', '' );
    $this->set_item( 'postcode', '' );
    $this->set_item( 'note', '' );

    $this->finish_setting_items();
  }
}
?>
