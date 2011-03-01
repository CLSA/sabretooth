<?php
/**
 * self_menu.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui;

/**
 * widget self menu
 * 
 * @package sabretooth\ui
 */
class self_menu extends widget
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
    parent::__construct( 'self', 'menu', $args );
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
    
    // get all 'list' widgets that the user has access to
    $modifier = new \sabretooth\database\modifier();
    $modifier->where( 'operation.type', 'widget' );
    $modifier->where( 'operation.name', 'list' );
    $widgets = \sabretooth\session::self()->get_role()->get_operation_list( $modifier );
    
    $items = array();
    foreach( $widgets as $db_widget )
    {
      array_push( $items, array( 'heading' => \sabretooth\util::pluralize( $db_widget->subject ),
                                 'widget' => $db_widget->subject.'_'.$db_widget->name ) );
    }

    $this->set_variable( 'items', $items );
  }
}
?>
