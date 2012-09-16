<?php
/**
 * phone_add.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui\widget;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * widget phone add
 */
class phone_add extends \cenozo\ui\widget\base_view
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
    parent::__construct( 'phone', 'add', $args );
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
    $this->add_item( 'address_id', 'enum', 'Associated address' );
    $this->add_item( 'active', 'boolean', 'Active' );
    $this->add_item( 'rank', 'enum', 'Rank' );
    $this->add_item( 'type', 'enum', 'Type' );
    $this->add_item( 'number', 'string', 'Phone' );
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
    
    // this widget must have a parent, and it's subject must be a participant
    if( is_null( $this->parent ) || 'participant' != $this->parent->get_subject() )
      throw lib::create( 'exception\runtime',
        'Phone widget must have a parent with participant as the subject.', __METHOD__ );

    // create enum arrays
    $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'participant_id', '=', $this->parent->get_record()->id ); 
    $modifier->order( 'rank' );
    $addresses = array();
    $address_class_name = lib::get_class_name( 'database\address' );
    foreach( $address_class_name::select( $modifier ) as $db_address )
    {
      $db_region = $db_address->get_region();
      $addresses[$db_address->id] = sprintf( '%d. %s, %s, %s',
        $db_address->rank,
        $db_address->city,
        $db_region->name,
        $db_region->country );
    }
    $num_phones = $this->parent->get_record()->get_phone_count();
    $ranks = array();
    for( $rank = 1; $rank <= ( $num_phones + 1 ); $rank++ ) $ranks[] = $rank;
    $ranks = array_combine( $ranks, $ranks );
    end( $ranks );
    $last_rank_key = key( $ranks );
    reset( $ranks );
    $phone_class_name = lib::get_class_name( 'database\phone' );
    $types = $phone_class_name::get_enum_values( 'type' );
    $types = array_combine( $types, $types );

    // set the view's items
    $this->set_item( 'participant_id', $this->parent->get_record()->id );
    $this->set_item( 'address_id', '', false, $addresses );
    $this->set_item( 'active', true, true );
    $this->set_item( 'rank', $last_rank_key, true, $ranks );
    $this->set_item( 'type', key( $types ), true, $types );
    $this->set_item( 'number', '', true );
    $this->set_item( 'note', '' );
  }
}
?>
