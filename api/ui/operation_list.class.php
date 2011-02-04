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
    $this->number_of_items = \sabretooth\database\operation::count();

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
   * Set the details of each operation as a row.
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
    if( 'type' == $this->sort_column ||
        'subject' == $this->sort_column || 
        'name' == $this->sort_column )
    {
      $sort = $this->sort_column;
    }
    else
    {
      $sort = NULL;
    }

    // get all operations
    $session = \sabretooth\session::self();
    $desc = $this->sort_desc;
    // TODO: restrict to role
    $db_operation_list = \sabretooth\database\operation::select( $limit_count, $limit_offset, $sort, $desc );
    foreach( $db_operation_list as $db_operation )
    {
      array_push( $this->rows, 
        array( 'id' => $db_operation->type.'.'.$db_operation->subject.'.'.$db_operation->name,
               'columns' => array( $db_operation->type,
                                   $db_operation->subject,
                                   $db_operation->name,
                                   $db_operation->restricted ? 'y' : 'n',
                                   $db_operation->description ) ) );
    }
  }
  
  /**
   * Restrict list to a specific role, or set to null for no restriction.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\role
   * @access public
   */
  public function set_role_restriction( $role )
  {
    $this->role_restriction = $role;
  }

  /**
   * The role to restrict the list to.
   * @var database\role
   * @access protected
   */
  protected $role_restriction = NULL;
}
?>
