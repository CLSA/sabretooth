<?php
/**
 * interview_method_list.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui\widget;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * widget interview_method list
 */
class interview_method_list extends \cenozo\ui\widget\base_list
{
  /**
   * Constructor
   * 
   * Defines all variables required by the interview_method list.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'interview_method', $args );
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

    $this->add_column( 'name', 'string', 'Name', true );
    $this->add_column( 'interviews', 'number', 'Interviews', false );
  }

  /**
   * Defines all rows in the list.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access protected
   */
  protected function setup()
  {
    parent::setup();
    
    foreach( $this->get_record_list() as $record )
    {
      $modifier = lib::create( 'database\modifier' );
      if( !is_null( $this->parent ) && 'qnaire' == $this->parent->get_subject() )
        $modifier->where( 'qnaire_id', '=', $this->parent->get_record()->id );

      $this->add_row( $record->id,
        array( 'name' => $record->name,
               'interviews' => $record->get_interview_count( $modifier ) ) );
    }
  }
}
