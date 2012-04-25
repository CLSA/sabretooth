<?php
/**
 * recording_list.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui\widget;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * widget recording list
 * 
 * @package sabretooth\ui
 */
class recording_list extends \cenozo\ui\widget\base_list
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
    // by default sort the rank column
    $this->sort_column = 'recording.rank';
    $this->sort_desc = false;

    parent::__construct( 'recording', $args );
    
    $this->add_column( 'interview.qnaire_name', 'string', 'Interview', false );
    $this->add_column( 'rank', 'number', 'Number', true );
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
      $this->add_row( $record->id,
        array( 'interview' => $record->get_interview()->get_qnaire()->name,
               'rank' => $record->rank,
               'file' => $record->get_file() ) );
    }

    $this->finish_setting_rows();
  }
}
?>
