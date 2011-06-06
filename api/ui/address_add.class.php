<?php
/**
 * address_add.class.php
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
 * widget address add
 * 
 * @package sabretooth\ui
 */
class address_add extends base_view
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
    parent::__construct( 'address', 'add', $args );

    // add items to the view
    $this->add_item( 'participant_id', 'hidden' );
    $this->add_item( 'active', 'boolean', 'Active' );
    $this->add_item( 'rank', 'enum', 'Rank' );
    $this->add_item( 'address1', 'string', 'Address1' );
    $this->add_item( 'address2', 'string', 'Address2' );
    $this->add_item( 'city', 'string', 'City' );
    $this->add_item( 'region_id', 'enum', 'Region' );
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
      throw new exc\runtime(
        'Address widget must have a parent with participant as the subject.', __METHOD__ );
    
    // create enum arrays
    $num_addresss = $this->parent->get_record()->get_address_count();
    $ranks = array();
    for( $rank = 1; $rank <= ( $num_addresss + 1 ); $rank++ ) $ranks[] = $rank;
    $ranks = array_combine( $ranks, $ranks );
    end( $ranks );
    $last_rank_key = key( $ranks );
    reset( $ranks );
    $regions = array();
    foreach( db\region::select() as $db_region )
      $regions[$db_region->id] = $db_region->name.', '.$db_region->country;
    reset( $regions );

    // set the view's items
    $this->set_item( 'participant_id', $this->parent->get_record()->id );
    $this->set_item( 'active', true, true );
    $this->set_item( 'rank', $last_rank_key, true, $ranks );
    $this->set_item( 'address1', '' );
    $this->set_item( 'address2', '' );
    $this->set_item( 'city', '' );
    $this->set_item( 'region_id', key( $regions ), true, $regions );
    $this->set_item( 'postcode', '' );
    $this->set_item( 'note', '' );

    $this->finish_setting_items();
  }
}
?>
