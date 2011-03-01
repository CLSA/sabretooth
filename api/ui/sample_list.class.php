<?php
/**
 * sample_list.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui;

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
    
    $session = \sabretooth\session::self();

    $this->columns = array(
      array( 'id' => 'name',
             'heading' => 'Name',
             'sortable' => true ),
      array( 'id' => 'participants',
             'heading' => 'Participants',
             'sortable' => false ),
      array( 'id' => 'qnaires',
             'heading' => 'Questionnaires',
             'sortable' => false ) );
  }
  
  /**
   * Set the rows array needed by the template.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access protected
   */
  protected function set_rows()
  {
    // reset the array
    $this->rows = array();
    
    foreach( $this->get_record_list() as $record )
    {
      // assemble the row for this record
      array_push(
        $this->rows, 
        array( 'id' => $record->id,
               'columns' =>
                 array( 'name' => $record->name,
                        'participants' => $record->get_participant_count(),
                        'qnaires' => $record->get_qnaire_count() ) ) );
    }
    \sabretooth\log::print_r( $this->rows );
  }
}
?>
