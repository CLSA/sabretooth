<?php
/**
 * qnaire_list.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui;
use sabretooth\log, sabretooth\util;

/**
 * widget qnaire list
 * 
 * @package sabretooth\ui
 */
class qnaire_list extends base_list_widget
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
    $this->add_column( 'phases', 'number', 'Stages', false );
    $this->add_column( 'samples', 'number', 'Samples', false );
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
      // assemble the row for this record
      $this->add_row( $record->id,
        array( 'name' => $record->name,
               'phases' => $record->get_phase_count(),
               'samples' => $record->get_sample_count() ) );
    }

    $this->finish_setting_rows();
  }
}
?>
