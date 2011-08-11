<?php
/**
 * appointment_view.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui\widget;
use sabretooth\log, sabretooth\util;
use sabretooth\business as bus;
use sabretooth\database as db;
use sabretooth\exception as exc;

/**
 * widget appointment view
 * 
 * @package sabretooth\ui
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
    parent::__construct( 'view', $args );
    
    // add items to the view
    $this->add_item( 'phone_id', 'enum', 'Phone Number',
      'Select a specific phone number to call for the appointment, or leave this field blank if '.
      'any of the participant\'s phone numbers can be called.' );
    $this->add_item( 'datetime', 'datetime', 'Date' );
    $this->add_item( 'assignment.user', 'constant', 'Assigned to' );
    $this->add_item( 'state', 'constant', 'State',
      '(One of reached, not reached, upcoming, assignable, missed, incomplete, assigned '.
      'or in progress)' );
  }

  /**
   * Finish setting the variables in a widget.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function finish()
  {
    // don't allow editing if the appointment has been assigned
    $db_assignment = $this->get_record()->get_assignment();
    $this->editable = is_null( $db_assignment );

    parent::finish();

    $db_participant = new db\participant( $this->get_record()->participant_id );
  
    // determine the time difference
    $db_phone = $this->get_record()->get_phone();

    // go with the phone's address if there is one, and the first address if not
    $db_address = is_null( $db_phone )
                ? $db_participant->get_first_address()
                : $db_phone->get_address();
    $time_diff = is_null( $db_address ) ? NULL : $db_address->get_time_diff();

    // need to add the participant's timezone information as information to the date item
    $site_name = bus\session::self()->get_site()->name;
    if( is_null( $time_diff ) )
      $note = 'The participant\'s time zone is not known.';
    else if( 0 == $time_diff )
      $note = sprintf( 'The participant is in the same time zone as the %s site.',
                       $site_name );
    else if( 0 < $time_diff )
      $note = sprintf( 'The participant\'s time zone is %s hours ahead of %s\'s time.',
                       $time_diff,
                       $site_name );
    else if( 0 > $time_diff )
      $note = sprintf( 'The participant\'s time zone is %s hours behind of %s\'s time.',
                       abs( $time_diff ),
                       $site_name );

    $this->add_item( 'datetime', 'datetime', 'Date', $note );
    
    // create enum arrays
    $modifier = new db\modifier();
    $modifier->where( 'active', '=', true );
    $modifier->order( 'rank' );
    $phones = array();
    foreach( $db_participant->get_phone_list( $modifier ) as $db_phone )
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

    // set the view's items
    $this->set_item( 'phone_id', $this->get_record()->phone_id, false, $phones );
    $this->set_item( 'datetime', $this->get_record()->datetime, true );
    $this->set_item( 'state', $this->get_record()->get_state(), false );

    $this->finish_setting_items();
  }
}
?>
