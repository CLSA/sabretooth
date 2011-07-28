<?php
/**
 * phone_add.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui\widget;
use sabretooth\log, sabretooth\util;
use sabretooth\business as bus;
use sabretooth\database as db;
use sabretooth\exception as exc;

/**
 * widget phone add
 * 
 * @package sabretooth\ui
 */
class phone_add extends base_view
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
   * @access public
   */
  public function finish()
  {
    parent::finish();
    
    // this widget must have a parent, and it's subject must be a participant
    if( is_null( $this->parent ) || 'participant' != $this->parent->get_subject() )
      throw new exc\runtime(
        'Phone widget must have a parent with participant as the subject.', __METHOD__ );

    // create enum arrays
    $modifier = new db\modifier();
    $modifier->where( 'participant_id', '=', $this->parent->get_record()->id ); 
    $modifier->order( 'rank' );
    $addresses = array();
    foreach( db\address::select( $modifier ) as $db_address )
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
    $types = db\phone::get_enum_values( 'type' );
    $types = array_combine( $types, $types );

    // set the view's items
    $this->set_item( 'participant_id', $this->parent->get_record()->id );
    $this->set_item( 'address_id', '', false, $addresses );
    $this->set_item( 'active', true, true );
    $this->set_item( 'rank', $last_rank_key, true, $ranks );
    $this->set_item( 'type', key( $types ), true, $types );
    $this->set_item( 'number', '', true );
    $this->set_item( 'note', '' );

    $this->finish_setting_items();
  }
}
?>
