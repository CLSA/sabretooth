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
 * Base class for all 'view' and 'add' widgets.
 * 
 * @package sabretooth\ui
 */
class role_view extends base_record
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
             'value' => $this->record->name );
    $this->item['operation_count'] =
      array( 'heading' => 'Operations',
             'type' => 'constant',
             'value' => $this->record->get_operation_count() );

    // create the operation sub-list widget
    $this->operation_list = new operation_list( $args );
    $this->operation_list->set_parent( $this );
    $this->operation_list->set_heading( 'Operations belonging to this role' );
    $this->operation_list->set_checkable( true );
    $this->operation_list->set_viewable( false );
    $this->operation_list->set_removable( true );
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
   * @return int
   * @access protected
   */
  public function determine_operation_count()
  {
    return $this->record->get_operation_count();
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
    return $this->record->get_operation_list( $modifier );
  }

  /**
   * The operation list widget.
   * @var operation_list
   * @access protected
   */
  protected $operation_list = NULL;
}
?>
