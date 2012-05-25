<?php
/**
 * assignment_view.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui\widget;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * widget assignment view
 * 
 * @package sabretooth\ui
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

    try
    {
      // create the phone_call sub-list widget
      $this->phone_call_list = lib::create( 'ui\widget\phone_call_list', $this->arguments );
      $this->phone_call_list->set_parent( $this );
      $this->phone_call_list->set_heading( 'Phone calls made during this assignment' );
    }
    catch( \cenozo\exception\permission $e )
    {
      $this->phone_call_list = NULL;
    }
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
    if( !is_null( $this->phone_call_list ) )
    {
      $this->phone_call_list->process();
      $this->set_variable( 'phone_call_list', $this->phone_call_list->get_variables() );
    }
  }
  
  /**
   * The assignment list widget.
   * @var phone_call_list
   * @access protected
   */
  protected $phone_call_list = NULL;
}
?>
