<?php
/**
 * address_view.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui\widget;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * widget address view
 */
class address_view extends \cenozo\ui\widget\base_view
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
    $this->add_item( 'active', 'boolean', 'Active' );
    $this->add_item( 'rank', 'enum', 'Rank' );
    $this->add_item( 'address1', 'string', 'Address1' );
    $this->add_item( 'address2', 'string', 'Address2' );
    $this->add_item( 'city', 'string', 'City' );
    $this->add_item( 'region_id', 'enum', 'Region' );
    $this->add_item( 'postcode', 'string', 'Postcode',
      'Postal codes must be in "A1A 1A1" format, zip codes in "01234" format.' );
    $this->add_item( 'timezone_offset', 'number', 'Timezone Offset',
      'The number of hours difference between the address\' timezone and UTC.' );
    $this->add_item( 'daylight_savings', 'boolean', 'Daylight Savings',
      'Whether the address observes daylight savings.' );
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

    $record = $this->get_record();

    $this->set_variable( 'january', $record->january );
    $this->set_variable( 'february', $record->february );
    $this->set_variable( 'march', $record->march );
    $this->set_variable( 'april', $record->april );
    $this->set_variable( 'may', $record->may );
    $this->set_variable( 'june', $record->june );
    $this->set_variable( 'july', $record->july );
    $this->set_variable( 'august', $record->august );
    $this->set_variable( 'september', $record->september );
    $this->set_variable( 'october', $record->october );
    $this->set_variable( 'november', $record->november );
    $this->set_variable( 'december', $record->december );

    // create enum arrays
    $num_addresss = $record->get_participant()->get_address_count();
    $ranks = array();
    for( $rank = 1; $rank <= $num_addresss; $rank++ ) $ranks[] = $rank;
    $ranks = array_combine( $ranks, $ranks );
    $regions = array();
    $class_name = lib::get_class_name( 'database\region' );
    foreach( $class_name::select() as $db_region )
      $regions[$db_region->id] = $db_region->name.', '.$db_region->country;

    // set the view's items
    $this->set_item( 'active', $record->active, true );
    $this->set_item( 'rank', $record->rank, true, $ranks );
    $this->set_item( 'address1', $record->address1 );
    $this->set_item( 'address2', $record->address2 );
    $this->set_item( 'city', $record->city );
    $this->set_item( 'region_id', $record->region_id, false, $regions );
    $this->set_item( 'postcode', $record->postcode, true );
    $this->set_item( 'timezone_offset', $record->timezone_offset, true );
    $this->set_item( 'daylight_savings', $record->daylight_savings, true );
    $this->set_item( 'note', $record->note );
  }
}
?>
