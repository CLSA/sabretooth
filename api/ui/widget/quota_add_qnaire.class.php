<?php
/**
 * quota_add_qnaire.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui\widget;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * widget quota add_qnaire
 */
class quota_add_qnaire extends \cenozo\ui\widget\base_add_list
{
  /**
   * Constructor
   * 
   * Defines all variables which need to be set for the associated template.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $name The name of the qnaire.
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'quota', 'qnaire', $args );
    $this->set_heading( '' );
  }

  /**
   * Overrides the qnaire list widget's method.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the list.
   * @return int
   * @access protected
   */
  public function determine_qnaire_count( $modifier = NULL )
  {
    $qnaire_class_name = lib::get_class_name( 'database\qnaire' );

    $existing_qnaire_ids = array();
    foreach( $this->get_record()->get_qnaire_list() as $db_qnaire )
      $existing_qnaire_ids[] = $db_qnaire->id;

    if( 0 < count( $existing_qnaire_ids ) )
    {
      if( is_null( $modifier ) ) $modifier = lib::create( 'database\modifier' );
      $modifier->where( 'id', 'NOT IN', $existing_qnaire_ids );
    }

    return $qnaire_class_name::count( $modifier );
  }

  /**
   * Overrides the qnaire list widget's method.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the list.
   * @return array( record )
   * @access protected
   */
  public function determine_qnaire_list( $modifier = NULL )
  {
    $qnaire_class_name = lib::get_class_name( 'database\qnaire' );

    $existing_qnaire_ids = array();
    foreach( $this->get_record()->get_qnaire_list() as $db_qnaire )
      $existing_qnaire_ids[] = $db_qnaire->id;

    if( 0 < count( $existing_qnaire_ids ) )
    {
      if( is_null( $modifier ) ) $modifier = lib::create( 'database\modifier' );
      $modifier->where( 'id', 'NOT IN', $existing_qnaire_ids );
    }

    return $qnaire_class_name::select( $modifier );
  }
}
