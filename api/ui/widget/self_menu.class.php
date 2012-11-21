<?php
/**
 * self_menu.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui\widget;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * widget self menu
 */
class self_menu extends \cenozo\ui\widget\self_menu
{
  /**
   * Processes arguments, preparing them for the operation.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @throws exception\notice
   * @access protected
   */
  protected function prepare()
  {
    parent::prepare();
    
    // remove the site calendar from the admin role
    $role = lib::create( 'business\session' )->get_role()->name;
    if( 'administrator' == $role ) $this->exclude_calendar( 'site' );

    $this->exclude_list( array(
      'address',
      'appointment',
      'availability',
      'callback',
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
   * @access protected
   */
  protected function setup()
  {
    parent::setup();

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
