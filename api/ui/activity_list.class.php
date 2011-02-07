<?php
/**
 * activity_list.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui;

/**
 * activity.list widget
 * 
 * @package sabretooth\ui
 */
class activity_list extends base_list
{
  /**
   * Constructor
   * 
   * Defines all variables required by the activity list.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'activity', $args );
    
    $session = \sabretooth\session::self();

    // define all template variables for this list
    $this->heading = 'Activity list';
    $this->checkable = false;
    $this->viewable = false;
    $this->editable = false;
    $this->removable = false;

    $this->columns = array(
      array( 'id' => 'user.name',
             'name' => 'user',
             'sortable' => true ),
      array( 'id' => 'site.name',
             'name' => 'site',
             'sortable' => true ),
      array( 'id' => 'role.name',
             'name' => 'role',
             'sortable' => true ),
      array( 'id' => 'operation.type',
             'name' => 'type',
             'sortable' => true ),
      array( 'id' => 'operation.subject',
             'name' => 'subject',
             'sortable' => true ),
      array( 'id' => 'operation.name',
             'name' => 'name',
             'sortable' => true ),
      array( 'id' => 'date',
             'name' => 'date',
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
      array_push( $this->rows, 
        array( 'id' => $record->id,
               'columns' => array( $record->get_user()->name,
                                   $record->get_site()->name,
                                   $record->get_role()->name,
                                   $record->get_operation()->type,
                                   $record->get_operation()->subject,
                                   $record->get_operation()->name,
                                   $record->date ) ) );
    }
  }
}
?>
