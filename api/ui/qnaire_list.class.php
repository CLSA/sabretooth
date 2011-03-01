<?php
/**
 * qnaire_list.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui;

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
    
    $session = \sabretooth\session::self();

    $this->columns = array(
      array( 'id' => 'name',
             'heading' => 'Name',
             'sortable' => true ),
      array( 'id' => 'samples',
             'heading' => 'Samples',
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
                        'samples' => $record->get_sample_count() ) ) );
    }
  }
}
?>
