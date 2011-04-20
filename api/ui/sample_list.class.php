<?php
/**
 * sample_list.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui;
use sabretooth\log, sabretooth\util;
use sabretooth\business as bus;
use sabretooth\database as db;
use sabretooth\exception as exc;

/**
 * widget sample list
 * 
 * @package sabretooth\ui
 */
class sample_list extends base_list_widget
{
  /**
   * Constructor
   * 
   * Defines all variables required by the sample list.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'sample', $args );
    
    $this->add_column( 'name', 'string', 'Name', true );
    $this->add_column( 'qnaire.name', 'string', 'Questionnaire', true );
    $this->add_column( 'participants', 'number', 'Participants', false );
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
      $db_qnaire = $record->get_qnaire();
      $qnaire = $db_qnaire ? $db_qnaire->name : '(none)';

      // assemble the row for this record
      $this->add_row( $record->id,
        array( 'name' => $record->name,
               'qnaire.name' => $qnaire,
               'participants' => $record->get_participant_count() ) );
    }

    $this->finish_setting_rows();
  }
}
?>
