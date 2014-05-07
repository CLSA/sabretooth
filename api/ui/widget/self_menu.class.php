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
    
    $interview_method_class_name = lib::get_class_name( 'database\interview_method' );
    $qnaire_class_name = lib::get_class_name( 'database\qnaire' );

    // remove the site calendar from the admin role
    $role = lib::create( 'business\session' )->get_role()->name;
    if( 'administrator' == $role ) $this->exclude_calendar( 'site' );

    // remove the IVR calendar if no qnaires use the IVR interview method
    $db_interview_method = $interview_method_class_name::get_unique_record( 'name', 'ivr' );
    if( !$qnaire_class_name::is_interview_method_in_use( $db_interview_method ) )
      $this->exclude_calendar( 'ivr_appointment' );

    $this->exclude_list( array(
      'appointment',
      'callback',
      'event_type',
      'interview_method',
      'ivr_appointment',
      'phase',
      'phone_call',
      'recording',
      'service',
      'source_survey',
      'source_withdraw',
      'survey' ) );
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

    // insert the participant tree into the utilities
    $db_operation = $operation_class_name::get_operation( 'widget', 'participant', 'tree' );
    if( lib::create( 'business\session' )->is_allowed( $db_operation ) )
      $utilities[] = array( 'heading' => 'Participant Tree',
                            'type' => 'widget',
                            'subject' => 'participant',
                            'name' => 'tree' );

    $this->set_variable( 'utilities', $utilities );
  }
}
