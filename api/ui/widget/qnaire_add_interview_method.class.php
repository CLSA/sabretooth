<?php
/**
 * qnaire_add_interview_method.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui\widget;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * widget qnaire add_interview_method
 */
class qnaire_add_interview_method extends \cenozo\ui\widget\base_add_list
{
  /**
   * Constructor
   * 
   * Defines all variables which need to be set for the associated template.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $name The name of the interview_method.
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'qnaire', 'interview_method', $args );
    $this->set_heading( '' );
  }

  /**
   * Overrides the interview_method list widget's method.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the list.
   * @return int
   * @access protected
   */
  public function determine_interview_method_count( $modifier = NULL )
  {
    $interview_method_class_name = lib::get_class_name( 'database\interview_method' );

    $existing_interview_method_ids = array();
    foreach( $this->get_record()->get_interview_method_list() as $db_interview_method )
      $existing_interview_method_ids[] = $db_interview_method->id;

    if( 0 < count( $existing_interview_method_ids ) )
    {
      if( is_null( $modifier ) ) $modifier = lib::create( 'database\modifier' );
      $modifier->where( 'id', 'NOT IN', $existing_interview_method_ids );
    }

    return $interview_method_class_name::count( $modifier );
  }

  /**
   * Overrides the interview_method list widget's method.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the list.
   * @return array( record )
   * @access protected
   */
  public function determine_interview_method_list( $modifier = NULL )
  {
    $interview_method_class_name = lib::get_class_name( 'database\interview_method' );

    $existing_interview_method_ids = array();
    foreach( $this->get_record()->get_interview_method_list() as $db_interview_method )
      $existing_interview_method_ids[] = $db_interview_method->id;

    if( 0 < count( $existing_interview_method_ids ) )
    {
      if( is_null( $modifier ) ) $modifier = lib::create( 'database\modifier' );
      $modifier->where( 'id', 'NOT IN', $existing_interview_method_ids );
    }

    return $interview_method_class_name::select( $modifier );
  }
}
