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
    $this->item['name'] =
      array( 'heading' => 'Name',
             'type' => 'string',
             'value' => $this->get_record()->name );
    $this->item['operation_count'] =
      array( 'heading' => 'Operations',
             'type' => 'constant',
             'value' => $this->get_record()->get_operation_count() );

    // create the operation sub-list widget
    $this->operation_list = new operation_list( $args );
    $this->operation_list->set_parent( $this );
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
  public function determine_operation_count( $modifier )
  {
    $modifier->where( 'restricted', 1 );
    return $this->get_record()->get_operation_count();
  }

  /**
   * Overrides the operation list widget's method.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the list.
   * @return array( active_record )
   * @access protected
   */
  public function determine_operation_list( $modifier )
  {
    $modifier->where( 'restricted', 1 );
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
