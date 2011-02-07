<?php
/**
 * role_view.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui;

/**
 * role.view widget
 * 
 * @package sabretooth\ui
 */
class role_view extends base_view
{
  /**
   * Constructor
   * 
   * Defines all variables which need to be set for the associated template.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'role', $args );

    // make sure to validate the arguments ($args could be anything)
    if( isset( $args['id'] ) && is_numeric( $args['id'] ) )
      $this->id = $args['id'];

    // make sure we have all the arguments necessary
    if( !isset( $this->id ) )
      throw new \sabretooth\exception\argument( 'id' );

    $db_role = new \sabretooth\database\role( $this->id );

    // define all template variables for this list
    $this->heading = 'Viewing role "'.$db_role->name.'"';
    $this->editable = true; // TODO: should be based on role
    $this->removable = false;
    
    // create an associative array with everything we want to display about the role
    $this->item = array( 'Name' => $db_role->name );

    // create the operation sub-list widget
    $this->operation_list = new operation_list( $args );
    $this->operation_list->set_parent( $this );
    $this->operation_list->set_heading( "Operations belonging to this role" );
    $this->operation_list->set_checkable( false );
    $this->operation_list->set_viewable( false );
    $this->operation_list->set_editable( false );
    $this->operation_list->set_removable( false );
    $this->operation_list->set_role_restriction( $db_role );
  }

  /**
   * Finish setting the variables in a widget.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function finish()
  {
    parent::finish();
    $this->operation_list->finish();
    
    // define all template variables for this widget
    $this->set_variable( 'id', $this->id );
    $this->operation_list->set_variable( 'id', $this->id );
    $this->set_variable( 'operation_list', $this->operation_list->get_variables() );
  }

  // TODO: document
  public function get_operation_list( $count = 0, $offset = 0, $column = NULL, $descending = false )
  {
    $db_role = new \sabretooth\database\role( $this->id );
    return $db_role->get_operation_list( $count, $offset, $column, $descending );
  }

  /**
   * The operation list widget.
   * @var operation_list
   * @access protected
   */
  protected $operation_list = NULL;

  /**
   * The primary key for the role being viewed.
   * @var int
   * @access protected
   */
  protected $id = NULL;
}
?>
