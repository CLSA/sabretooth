<?php
/**
 * address_view.class.php
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
 * widget address view
 * 
 * @package sabretooth\ui
 */
class address_view extends base_view
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
    parent::__construct( 'address', 'view', $args );
    
    // add items to the view
    $this->add_item( 'active', 'boolean', 'Active' );
    $this->add_item( 'rank', 'enum', 'Rank' );
    $this->add_item( 'address1', 'string', 'Address1' );
    $this->add_item( 'address2', 'string', 'Address2' );
    $this->add_item( 'city', 'string', 'City' );
    $this->add_item( 'region_id', 'enum', 'Region' );
    $this->add_item( 'postcode', 'string', 'Postcode',
      'Postal codes must be in "A1A 1A1" format, zip codes in "01234" format.' );
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

    $this->set_variable( 'january', $this->get_record()->january );
    $this->set_variable( 'february', $this->get_record()->february );
    $this->set_variable( 'march', $this->get_record()->march );
    $this->set_variable( 'april', $this->get_record()->april );
    $this->set_variable( 'may', $this->get_record()->may );
    $this->set_variable( 'june', $this->get_record()->june );
    $this->set_variable( 'july', $this->get_record()->july );
    $this->set_variable( 'august', $this->get_record()->august );
    $this->set_variable( 'september', $this->get_record()->september );
    $this->set_variable( 'october', $this->get_record()->october );
    $this->set_variable( 'november', $this->get_record()->november );
    $this->set_variable( 'december', $this->get_record()->december );

    // create enum arrays
    $num_addresss = $this->get_record()->get_participant()->get_address_count();
    $ranks = array();
    for( $rank = 1; $rank <= $num_addresss; $rank++ ) $ranks[] = $rank;
    $ranks = array_combine( $ranks, $ranks );
    $regions = array();
    foreach( db\region::select() as $db_region )
      $regions[$db_region->id] = $db_region->name.', '.$db_region->country;

    // set the view's items
    $this->set_item( 'active', $this->get_record()->active, true );
    $this->set_item( 'rank', $this->get_record()->rank, true, $ranks );
    $this->set_item( 'address1', $this->get_record()->address1 );
    $this->set_item( 'address2', $this->get_record()->address2 );
    $this->set_item( 'city', $this->get_record()->city );
    $this->set_item( 'region_id', $this->get_record()->region_id, false, $regions );
    $this->set_item( 'postcode', $this->get_record()->postcode, true );
    $this->set_item( 'note', $this->get_record()->note );

    $this->finish_setting_items();
  }
}
?>
