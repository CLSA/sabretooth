<?php
/**
 * qnaire_list.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui\widget;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * widget qnaire list
 */
class qnaire_list extends \cenozo\ui\widget\base_list
{
  /**
   * Constructor
   * 
   * Defines all variables required by the qnaire list.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'qnaire', $args );
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
    $this->add_column( 'rank', 'number', 'Rank', true );
    $this->add_column( 'prev_qnaire', 'string', 'Previous', false );
    $this->add_column( 'delay', 'number', 'Delay (weeks)', false );
    $this->add_column( 'phases', 'number', 'Stages', false );
  }
  
  /**
   * Set the rows array needed by the template.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access protected
   */
  protected function setup()
  {
    parent::setup();
    
    foreach( $this->get_record_list() as $record )
    {
      $prev_qnaire = 'none';
      if( !is_null( $record->prev_qnaire_id ) )
      {
        $db_prev_qnaire = lib::create( 'database\qnaire', $record->prev_qnaire_id );
        $prev_qnaire = $db_prev_qnaire->name;
      }

      // assemble the row for this record
      $this->add_row( $record->id,
        array( 'name' => $record->name,
               'rank' => $record->rank,
               'prev_qnaire' => $prev_qnaire,
               'delay' => $record->delay,
               'phases' => $record->get_phase_count() ) );
    }
  }
}
?>
