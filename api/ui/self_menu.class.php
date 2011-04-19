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

    $session = \sabretooth\business\session::self();
    $db_role = $session->get_role();
    $is_operator = 'operator' == $db_role->name;

    $items = array();
    if( $is_operator )
    {
      $items[] = array( 'heading' => 'Assignment',
                        'widget' => 'operator_assignment' );
    }

    // get all 'list' widgets that the user has access to
    $modifier = new \sabretooth\database\modifier();
    $modifier->where( 'operation.type', '=', 'widget' );
    $modifier->where( 'operation.name', '=', 'list' );
    $widgets = $db_role->get_operation_list( $modifier );
    
    $exclude = array( 'availability', 'consent', 'contact', 'phase', 'phone_call' );

    foreach( $widgets as $db_widget )
    {
      // don't include the appointment list in the operator's menu
      if( $is_operator &&
          ( 'appointment' == $db_widget->subject ||
            'assignment' == $db_widget->subject ) ) continue;

      if( !in_array( $db_widget->subject, $exclude ) )
        $items[] = array( 'heading' => \sabretooth\util::pluralize( $db_widget->subject ),
                          'widget' => $db_widget->subject.'_'.$db_widget->name );

      // insert the participant tree after participant list
      if( 'participant' == $db_widget->subject )
        $items[] = array( 'heading' => 'Participant Tree',
                          'widget' => 'participant_tree' );
    }

    $this->set_variable( 'items', $items );
  }
}
?>
