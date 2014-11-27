<?php
/**
 * ivr_appointment_view.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui\widget;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * widget ivr_appointment view
 */
class ivr_appointment_view extends base_appointment_view
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
    parent::__construct( 'ivr_appointment', 'view', $args );
  }

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

    // override the base calendar type
    $this->calendar = lib::create( 'ui\widget\ivr_appointment_calendar', $this->arguments );
    $this->calendar->set_parent( $this );
    $this->calendar->set_variable( 'default_view', 'basicWeek' );
    
    // add items to the view
    $this->add_item( 'uid', 'constant', 'UID' );
    $this->add_item( 'phone_id', 'enum', 'Phone Number',
      'Select which phone number to call from the IVR system.' );
    $this->add_item( 'state', 'constant', 'State', '(One of complete, incomplete or upcoming)' );
  }

  /**
   * Finish setting the variables in a widget.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access protected
   */
  protected function setup()
  {
    $operation_class_name = lib::get_class_name( 'database\operation' );

    // don't allow editing if the ivr_appointment's completed state is set
    if( true == $this->get_editable() ) $this->set_editable( is_null( $this->get_record()->completed ) );

    parent::setup();

    // create enum arrays
    $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'active', '=', true );
    $modifier->order( 'rank' );
    $phones = array();
    foreach( $this->db_participant->get_phone_list( $modifier ) as $db_phone )
      $phones[$db_phone->id] = $db_phone->rank.". ".$db_phone->number;
    
    // set the view's items
    $this->set_item( 'uid', $this->db_participant->uid );
    $this->set_item( 'phone_id', $this->get_record()->phone_id, true, $phones );
    $this->set_item( 'datetime', $this->get_record()->datetime, true );
    $this->set_item( 'state', $this->get_record()->get_state(), false );

    // hide the calendar if requested to
    $this->set_variable( 'hide_calendar', $this->get_argument( 'hide_calendar', false ) );
    $this->set_variable( 'interview_id', $this->db_interview->id );
    $this->set_variable( 'participant_id', $this->db_participant->id );

    // add an action to view the interview and participant's details
    $db_operation = $operation_class_name::get_operation( 'widget', 'participant', 'view' );
    if( lib::create( 'business\session' )->is_allowed( $db_operation ) )
      $this->add_action(
        'view_participant',
        'View Participant',
        NULL,
        'View the participant\'s details' );
    $db_operation = $operation_class_name::get_operation( 'widget', 'interview', 'view' );
    if( lib::create( 'business\session' )->is_allowed( $db_operation ) )
      $this->add_action(
        'view_interview',
        'View Participant',
        NULL,
        'View the interview\'s details' );
  }
}
