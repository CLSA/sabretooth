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
    
    // remove the site calendar from the admin and clerk roles
    $role = lib::create( 'business\session' )->get_role()->name;
    if( 'administrator' == $role || 'clerk' == $role ) $this->exclude_calendar( 'site' );

    $this->exclude_list( array(
      'address',
      'appointment',
      'availability',
      'consent',
      'operation',
      'phase',
      'phone',
      'phone_call',
      'recording',
      'source_survey',
      'source_withdraw' ) );
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

    $operation_class_name = lib::get_class_name( 'database\operation' );
    $utilities = $this->get_variable( 'utilities' );
    $session = lib::create( 'business\session' );

    // insert the participant tree into the utilities
    $db_operation = $operation_class_name::get_operation( 'widget', 'participant', 'tree' );
    if( $session->is_allowed( $db_operation ) )
      $utilities[] = array( 'heading' => 'Participant Tree',
                            'type' => 'widget',
                            'subject' => 'participant',
                            'name' => 'tree' );

    // insert the participant sync operation into the utilities
    $db_operation = $operation_class_name::get_operation( 'widget', 'participant', 'sync' );
    if( $session->is_allowed( $db_operation ) )
      $utilities[] = array( 'heading' => 'Participant Sync',
                            'type' => 'widget',
                            'subject' => 'participant',
                            'name' => 'sync' );

    $this->set_variable( 'utilities', $utilities );
  }
}
?>
