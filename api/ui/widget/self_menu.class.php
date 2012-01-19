<?php
/**
 * self_menu.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui\widget;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * widget self menu
 * 
 * @package sabretooth\ui
 */
class self_menu extends \cenozo\ui\widget\self_menu
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
    parent::__construct( $args );
    
    $exclude = array(
      'address',
      'appointment',
      'availability',
      'consent',
      'operation',
      'phase',
      'phone',
      'phone_call',
      'recording',
      'source_survey' );
    $this->exclude_widget_list = array_merge( $this->exclude_widget_list, $exclude );
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

    $utilities = $this->get_variable( 'utilities' );

    // insert the participant tree into the utilities
    $operation_class_name = lib::get_class_name( 'database\operation' );
    $db_operation = $operation_class_name::get_operation( 'widget', 'participant', 'tree' );
    if( lib::create( 'business\session' )->is_allowed( $db_operation ) )
      $utilities[] = array( 'heading' => 'Participant Tree',
                            'subject' => 'participant',
                            'name' => 'tree' );

    $this->set_variable( 'utilities', $utilities );
  }
}
?>
