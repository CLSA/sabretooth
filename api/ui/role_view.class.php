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
    $ui_operation_list = new operation_list( $args );
    $ui_operation_list->set_role_restriction( $db_role );
    $ui_operation_list->finish();
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

    // define all template variables for this widget
    $this->set_variable( 'id', $this->id );
  }

  /**
   * The primary key for the role being viewed.
   * @var int
   * @access protected
   */
  protected $id = NULL;
}
?>
