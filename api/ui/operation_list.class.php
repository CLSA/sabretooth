<?php
/**
 * operation_list.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui;

/**
 * operation.list widget
 * 
 * @package sabretooth\ui
 */
class operation_list extends base_list
{
  /**
   * Constructor
   * 
   * Defines all variables required by the operation list.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $args = NULL )
  {
    parent::__construct( 'operation', $args );
    
    $session = \sabretooth\session::self();

    // define all template variables for this list
    $this->heading =  "Operation list";
    $this->checkable =  false;
    $this->viewable =  true; // TODO: should be based on role
    $this->editable =  false;
    $this->removable =  false;

    $this->columns = array(
      array( "id" => "type",
             "name" => "type",
             "sortable" => true ),
      array( "id" => "subject",
             "name" => "subject",
             "sortable" => true ),
      array( "id" => "name",
             "name" => "name",
             "sortable" => true ),
      array( "id" => "restricted",
             "name" => "restricted",
             "sortable" => false ),
      array( "id" => "description",
             "name" => "description",
             "sortable" => false,
             "align" => "left" ) );
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
      array_push( $this->rows, 
        array( 'id' => $record->type.'.'.$record->subject.'.'.$record->name,
               'columns' => array( $record->type,
                                   $record->subject,
                                   $record->name,
                                   $record->restricted ? 'yes' : 'no',
                                   $record->description ) ) );
    }
  }
}
?>
