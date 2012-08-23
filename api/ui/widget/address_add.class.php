<?php
/**
 * address_add.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui\widget;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * widget address add
 */
class address_add extends \cenozo\ui\widget\base_view
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
    $this->add_item( 'active', 'boolean', 'Active' );
    $this->add_item( 'rank', 'enum', 'Rank' );
    $this->add_item( 'address1', 'string', 'Address1' );
    $this->add_item( 'address2', 'string', 'Address2' );
    $this->add_item( 'city', 'string', 'City' );
    $this->add_item( 'postcode', 'string', 'Postcode',
      'Postal codes must be in "A1A 1A1" format, zip codes in "01234" format. '.
      'NOTE: province, timezone and daylight savings will be determined by the postcode.' );
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
    
    $this->set_variable( 'january', true );
    $this->set_variable( 'february', true );
    $this->set_variable( 'march', true );
    $this->set_variable( 'april', true );
    $this->set_variable( 'may', true );
    $this->set_variable( 'june', true );
    $this->set_variable( 'july', true );
    $this->set_variable( 'august', true );
    $this->set_variable( 'september', true );
    $this->set_variable( 'october', true );
    $this->set_variable( 'november', true );
    $this->set_variable( 'december', true );

    // this widget must have a parent, and it's subject must be a participant
    if( is_null( $this->parent ) || 'participant' != $this->parent->get_subject() )
      throw lib::create( 'exception\runtime',
        'Address widget must have a parent with participant as the subject.', __METHOD__ );
    
    // create enum arrays
    $num_addresss = $this->parent->get_record()->get_address_count();
    $ranks = array();
    for( $rank = 1; $rank <= ( $num_addresss + 1 ); $rank++ ) $ranks[] = $rank;
    $ranks = array_combine( $ranks, $ranks );
    end( $ranks );
    $last_rank_key = key( $ranks );
    reset( $ranks );

    // set the view's items
    $this->set_item( 'participant_id', $this->parent->get_record()->id );
    $this->set_item( 'active', true, true );
    $this->set_item( 'rank', $last_rank_key, true, $ranks );
    $this->set_item( 'address1', '' );
    $this->set_item( 'address2', '' );
    $this->set_item( 'city', '' );
    $this->set_item( 'postcode', '' );
    $this->set_item( 'note', '' );
  }
}
?>
