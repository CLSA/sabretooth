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
  public function __construct( $args = NULL )
  {
    parent::__construct( 'role', $args );
    
    $session = \sabretooth\session::self();

    // define all template variables for this list
    $this->heading =  "Role list";
    $this->checkable =  false;
    $this->viewable =  true; // TODO: should be based on role
    $this->editable =  false;
    $this->removable =  false;
    $this->number_of_items = \sabretooth\database\role::count();

    $this->columns = array(
      array( "id" => "name",
             "name" => "name",
             "sortable" => true ),
      array( "id" => "users",
             "name" => "users",
             "sortable" => false ) );
  }

  /**
   * Set the details of each role as a row.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param int $limit_count The number of rows to include.
   * @param int $limit_count The offset to start rows at.
   * @access protected
   */
  protected function set_rows( $limit_count, $limit_offset )
  {
    // reset the array
    $this->rows = array();
    
    // determine what we're sorting by
    if( 'name' == $this->sort_column )
    {
      $sort = 'name';
    }
    else
    {
      $sort = NULL;
    }

    // get all roles
    $session = \sabretooth\session::self();
    $desc = $this->sort_desc;
    $db_role_list = $this->get_db_list( $limist_count, $limit_offset, $sort, $desc );
    foreach( $db_role_list as $db_role )
    {
      array_push( $this->rows, 
        array( 'id' => $db_role->id,
               'columns' => array( $db_role->name, $db_role->get_user_count() ) ) );
    }
  }
}
?>
