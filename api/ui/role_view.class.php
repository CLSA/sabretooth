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

    // define all template variables for this list
    $this->heading = 'Viewing role "'.$this->record->name.'"';
    $this->editable = true; // TODO: should be based on role
    $this->removable = false;
    
    // create an associative array with everything we want to display about the role
    $this->item = array( 'Name' => $this->record->name,
                         'Operations' => $this->record->get_operation_count() );

    // create the operation sub-list widget
    $this->operation_list = new operation_list( $args );
    $this->operation_list->set_parent( $this );
    $this->operation_list->set_heading( 'Operations belonging to this role' );
    $this->operation_list->set_checkable( false );
    $this->operation_list->set_viewable( false );
    $this->operation_list->set_editable( false );
    $this->operation_list->set_removable( false );
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
    $this->set_variable( 'id', $this->record->id );
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
   * @param int $count The number of rows to include.
   * @param int $offset The offset to start rows at.
   * @param string $sort The column to sort the list by.
   * @param boolean $desc Whether to sort in descending or ascending order.
   * @return array( active_record )
   * @access protected
   */
  public function determine_operation_list( $count, $offset, $column, $desc )
  {
    return $this->record->get_operation_list( $count, $offset, $column, $desc );
  }

  /**
   * The operation list widget.
   * @var operation_list
   * @access protected
   */
  protected $operation_list = NULL;
}
?>
