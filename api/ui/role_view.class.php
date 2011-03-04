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
 * widget role view
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
   * @param string $name The name of the operation.
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'role', 'view', $args );

    // create an associative array with everything we want to display about the role
    $this->add_item( 'name', 'string', 'Name' );
    $this->add_item( 'operation_count', 'constant', 'Operations' );

    // create the operation sub-list widget
    $this->operation_list = new operation_list( $args );
    $this->operation_list->set_parent( $this );
    $this->operation_list->remove_column( 'restricted' );
    $this->operation_list->set_heading( 'Operations belonging to this role' );
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

    // set the view's items
    $this->set_item( 'name', $this->get_record()->name );
    $this->set_item( 'operation_count', $this->get_record()->get_operation_count() );

    $this->finish_setting_items();

    // finish the child widgets
    $this->operation_list->finish();
    $this->set_variable( 'operation_list', $this->operation_list->get_variables() );
  }

  /**
   * Overrides the operation list widget's method.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the list.
   * @return int
   * @access protected
   */
  public function determine_operation_count( $modifier = NULL )
  {
    return $this->get_record()->get_operation_count( $modifier );
  }

  /**
   * Overrides the operation list widget's method.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the list.
   * @return array( active_record )
   * @access protected
   */
  public function determine_operation_list( $modifier = NULL )
  {
    return $this->get_record()->get_operation_list( $modifier );
  }

  /**
   * The operation list widget.
   * @var operation_list
   * @access protected
   */
  protected $operation_list = NULL;
}
?>
