<?php
/**
 * role_view.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui\widget;
use sabretooth\log, sabretooth\util;
use sabretooth\business as bus;
use sabretooth\database as db;
use sabretooth\exception as exc;

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
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'role', 'view', $args );

    // create an associative array with everything we want to display about the role
    $this->add_item( 'name', 'string', 'Name' );
    $this->add_item( 'users', 'constant', 'Number of users' );

    try
    {
      // create the operation sub-list widget
      $this->operation_list = new operation_list( $args );
      $this->operation_list->set_parent( $this );
      $this->operation_list->remove_column( 'restricted' );
      $this->operation_list->set_heading( 'Operations belonging to this role' );
    }
    catch( exc\permission $e )
    {
      $this->operation_list = NULL;
    }
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
    $this->set_item( 'name', $this->get_record()->name, true );
    $this->set_item( 'users', $this->get_record()->get_user_count() );

    $this->finish_setting_items();

    // finish the child widgets
    if( !is_null( $this->operation_list ) )
    {
      $this->operation_list->finish();
      $this->set_variable( 'operation_list', $this->operation_list->get_variables() );
    }
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
   * @return array( record )
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
