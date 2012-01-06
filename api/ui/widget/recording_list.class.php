<?php
/**
 * recording_list.class.php
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
 * widget recording list
 * 
 * @package sabretooth\ui
 */
class recording_list extends base_list
{
  /**
   * Constructor
   * 
   * Defines all variables required by the recording list.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'recording', $args );
    
    $this->add_column( 'interview.qnaire_name', 'string', 'Interview', false );
    $this->add_column( 'rank', 'number', 'Number', true );
    $this->add_column( 'processed', 'boolean', 'Processed', true );
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

    $this->set_variable( 'sip_enabled', bus\voip_manager::self()->get_sip_enabled() );

    foreach( $this->get_record_list() as $record )
    {
      $this->add_row( $record->id,
        array( 'interview' => $record->get_interview()->get_qnaire()->name,
               'rank' => $record->rank,
               'processed' => $record->processed,
               'file' => $record->get_file() ) );
    }

    $this->finish_setting_rows();
  }
}
?>
