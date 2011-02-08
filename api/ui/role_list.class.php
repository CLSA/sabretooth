<?php
/**
 * role_list.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui;

/**
 * role.list widget
 * 
 * @package sabretooth\ui
 */
class role_list extends base_list
{
  /**
   * Constructor
   * 
   * Defines all variables required by the role list.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'role', $args );
    
    $session = \sabretooth\session::self();

    // define all template variables for this list
    $this->heading =  'Role list';
    $this->checkable =  false;
    $this->viewable =  true; // TODO: should be based on role
    $this->editable =  false;
    $this->removable =  false;

    $this->columns = array(
      array( 'id' => 'name',
             'name' => 'name',
             'sortable' => true ),
      array( 'id' => 'users',
             'name' => 'users',
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
      array_push( $this->rows, 
        array( 'id' => $record->id,
               'columns' => array( $record->name, $record->get_user_count() ) ) );
    }
  }
}
?>
