<?php
/**
 * appointment_view.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui\widget;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * widget appointment view
 */
class appointment_view extends base_appointment_view
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
    parent::__construct( 'appointment', 'view', $args );
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
    
    $this->db_participant =
      lib::create( 'database\participant', $this->get_record()->participant_id );

    // add items to the view
    $this->add_item( 'uid', 'constant', 'UID' );
    $this->add_item( 'phone_id', 'enum', 'Phone Number',
      'Select a specific phone number to call for the appointment, or leave this field blank if '.
      'any of the participant\'s phone numbers can be called.' );
    $this->add_item( 'assignment.user', 'constant', 'Assigned to' );
    $this->add_item( 'state', 'constant', 'State',
      '(One of reached, not reached, upcoming, assignable, missed, incomplete, assigned '.
      'or in progress)' );
    $this->add_item( 'type', 'enum', 'Type' );
  }

  /**
   * Finish setting the variables in a widget.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access protected
   */
  protected function setup()
  {
    $db_assignment = $this->get_record()->get_assignment();

    $operation_class_name = lib::get_class_name( 'database\operation' );

    // don't allow editing if the appointment has been assigned
    if( true == $this->get_editable() ) $this->set_editable( is_null( $db_assignment ) );

    parent::setup();

    // determine the time difference: use the first address unless there is a phone number with
    // an address
    $db_phone = $this->get_record()->get_phone();

    // create enum arrays
    $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'active', '=', true );
    $modifier->order( 'rank' );
    $phones = array();
    foreach( $this->db_participant->get_phone_list( $modifier ) as $db_phone )
      $phones[$db_phone->id] = $db_phone->rank.". ".$db_phone->number;
    
    if( !is_null( $db_assignment ) )
    {
      $this->set_item( 'assignment.user', $db_assignment->get_user()->name, false );

      $this->add_item( 'assignment.start_datetime', 'constant', 'Started' );
      $this->set_item( 'assignment.start_datetime',
        util::get_formatted_time( $db_assignment->start_datetime ), false );
      
      $this->add_item( 'assignment.end_datetime', 'constant', 'Finished' );
      $this->set_item( 'assignment.end_datetime',
        util::get_formatted_time( $db_assignment->end_datetime ), false );
    }
    else
    {
      $this->set_item( 'assignment.user', 'unassigned', false );
    }

    $appointment_class_name = lib::get_class_name( 'database\appointment' );
    $types = $appointment_class_name::get_enum_values( 'type' );
    $types = array_combine( $types, $types );

    // set the view's items
    $this->set_item( 'uid', $this->db_participant->uid );
    $this->set_item( 'phone_id', $this->get_record()->phone_id, false, $phones );
    $this->set_item( 'datetime', $this->get_record()->datetime, true );
    $this->set_item( 'state', $this->get_record()->get_state(), false );
    $this->set_item( 'type', $this->get_record()->type, false, $types );

    // hide the calendar if requested to
    $this->set_variable( 'hide_calendar', $this->get_argument( 'hide_calendar', false ) );
    $this->set_variable( 'participant_id', $this->db_participant->id );

    // add an action to view the participant's details
    $db_operation = $operation_class_name::get_operation( 'widget', 'participant', 'view' );
    if( lib::create( 'business\session' )->is_allowed( $db_operation ) )
      $this->add_action(
        'view_participant',
        'View Participant',
        NULL,
        'View the participant\'s details' );
  }
}
