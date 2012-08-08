<?php
/**
 * assignment_view.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui\widget;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * widget assignment view
 */
class assignment_view extends \cenozo\ui\widget\base_view
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
    parent::__construct( 'assignment', 'view', $args );
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

    // create an associative array with everything we want to display about the assignment
    $this->add_item( 'user', 'constant', 'User' );
    $this->add_item( 'site', 'constant', 'Site' );
    $this->add_item( 'participant', 'constant', 'Participant' );
    $this->add_item( 'queue', 'constant', 'Queue' );
    $this->add_item( 'datetime', 'constant', 'Date' );
    $this->add_item( 'start_time_only', 'constant', 'Start Time' );
    $this->add_item( 'end_time_only', 'constant', 'End Time' );

    // create the phone_call sub-list widget
    $this->phone_call_list = lib::create( 'ui\widget\phone_call_list', $this->arguments );
    $this->phone_call_list->set_parent( $this );
    $this->phone_call_list->set_heading( 'Phone calls made during this assignment' );
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
       
    $db_participant = $this->get_record()->get_interview()->get_participant();
    $participant = sprintf( '%s, %s', $db_participant->last_name, $db_participant->first_name );

    // set the view's items
    $this->set_item( 'user', $this->get_record()->get_user()->name );
    $this->set_item( 'site', $this->get_record()->get_site()->name );
    $this->set_item( 'participant', $participant );
    $this->set_item( 'queue', $this->get_record()->get_queue()->name );
    $this->set_item( 'datetime',
      util::get_formatted_date( $this->get_record()->start_datetime ) );
    $this->set_item( 'start_time_only',
      util::get_formatted_time( $this->get_record()->start_datetime, false ) );
    $this->set_item( 'end_time_only',
      util::get_formatted_time( $this->get_record()->end_datetime, false, 'none' ) );

    // process the child widgets
    try
    {
      $this->phone_call_list->process();
      $this->set_variable( 'phone_call_list', $this->phone_call_list->get_variables() );
    }
    catch( \cenozo\exception\permission $e ) {}

    // add an action to view the participant's details
    $db_operation = $operation_class_name::get_operation( 'widget', 'participant', 'view' );
    if( lib::create( 'business\session' )->is_allowed( $db_operation ) )
      $this->add_action(
        'view_participant',
        'View Participant',
        NULL,
        'View the participant\'s details' );
    $this->set_variable( 'participant_id', $db_participant->id );

    // add a listen-in action to the active call for this assignment
    $db_assignment = $this->get_record();
    $db_user = $db_assignment->get_user();
    $db_operation = $operation_class_name::get_operation( 'push', 'voip', 'spy' );
    if( lib::create( 'business\session' )->is_allowed( $db_operation ) )
    { // if the user is allowed to spy
      $phone_call_mod = lib::create( 'database\modifier' );
      $phone_call_mod->where( 'end_datetime', '=', NULL );
      $open_call_count = $db_assignment->get_phone_call_count( $phone_call_mod );
      if( 0 < $open_call_count )
      { // if this assignment has an open call
        $voip_manager = lib::create( 'business\voip_manager' );
        if( $voip_manager->get_sip_enabled() && $voip_manager->get_call( $db_user ) )
        { // and if sip is enabled and the user has an active call
          $this->add_action(
            'voip_spy',
            'Listen In',
            NULL,
            'Listen in on the phone call currently in progress for this assignment.' );
        }
      }
    }
    $this->set_variable( 'user_id', $db_user->id );
  }
  
  /**
   * The assignment list widget.
   * @var phone_call_list
   * @access protected
   */
  protected $phone_call_list = NULL;
}
?>
