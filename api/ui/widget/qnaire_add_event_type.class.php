<?php
/**
 * qnaire_add_event_type.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui\widget;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * widget qnaire add_event_type
 */
class qnaire_add_event_type extends \cenozo\ui\widget\base_add_list
{
  /**
   * Constructor
   * 
   * Defines all variables which need to be set for the associated template.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $name The name of the event_type.
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'qnaire', 'event_type', $args );
  }

  /**
   * Overrides the event_type list widget's method.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the list.
   * @return int
   * @access protected
   */
  public function determine_event_type_count( $modifier = NULL )
  {
    $event_type_class_name = lib::get_class_name( 'database\event_type' );

    $existing_event_type_ids = array();
    foreach( $this->get_record()->get_event_type_list() as $db_event_type )
      $existing_event_type_ids[] = $db_event_type->id;

    if( 0 < count( $existing_event_type_ids ) )
    {
      if( is_null( $modifier ) ) $modifier = lib::create( 'database\modifier' );
      $modifier->where( 'id', 'NOT IN', $existing_event_type_ids );
    }

    return $event_type_class_name::count( $modifier );
  }

  /**
   * Overrides the event_type list widget's method.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the list.
   * @return array( record )
   * @access protected
   */
  public function determine_event_type_list( $modifier = NULL )
  {
    $event_type_class_name = lib::get_class_name( 'database\event_type' );

    $existing_event_type_ids = array();
    foreach( $this->get_record()->get_event_type_list() as $db_event_type )
      $existing_event_type_ids[] = $db_event_type->id;

    if( 0 < count( $existing_event_type_ids ) )
    {
      if( is_null( $modifier ) ) $modifier = lib::create( 'database\modifier' );
      $modifier->where( 'id', 'NOT IN', $existing_event_type_ids );
    }

    return $event_type_class_name::select( $modifier );
  }
}
