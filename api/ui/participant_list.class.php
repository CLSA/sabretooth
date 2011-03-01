<?php
/**
 * participant_list.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui;

/**
 * widget participant list
 * 
 * @package sabretooth\ui
 */
class participant_list extends base_list_widget
{
  /**
   * Constructor
   * 
   * Defines all variables required by the participant list.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'participant', $args );
    
    $session = \sabretooth\session::self();

    $this->columns = array(
      array( 'id' => 'first_name',
             'heading' => 'First Name',
             'sortable' => true ),
      array( 'id' => 'last_name',
             'heading' => 'Last Name',
             'sortable' => true ),
      array( 'id' => 'language',
             'heading' => 'Language',
             'sortable' => true ),
      array( 'id' => 'status',
             'heading' => 'Condition',
             'sortable' => true ) );
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
                 array( 'first_name' => $record->first_name,
                        'last_name' => $record->last_name,
                        'language' => $record->language,
                        'status' => $record->status ? $record->status : '(none)' ) ) );
    }
  }
}
?>
