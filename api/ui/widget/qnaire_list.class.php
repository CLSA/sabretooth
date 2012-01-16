<?php
/**
 * qnaire_list.class.php
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
 * widget qnaire list
 * 
 * @package sabretooth\ui
 */
class qnaire_list extends base_list
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
   * @access public
   */
  public function finish()
  {
    parent::finish();
    
    foreach( $this->get_record_list() as $record )
    {
      $prev_qnaire = 'none';
      if( !is_null( $record->prev_qnaire_id ) )
      {
        $db_prev_qnaire = new db\qnaire( $record->prev_qnaire_id );
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

    $this->finish_setting_rows();
  }
}
?>
