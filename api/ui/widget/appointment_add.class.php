<?php
/**
 * appointment_add.class.php
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
 * widget appointment add
 * 
 * @package sabretooth\ui
 */
class appointment_add extends base_appointment_view
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
    parent::__construct( 'add', $args );
    
    // add items to the view
    $this->add_item( 'participant_id', 'hidden' );
    $this->add_item( 'phone_id', 'enum', 'Phone Number',
      'Select a specific phone number to call for the appointment, or leave this field blank if '.
      'any of the participant\'s phone numbers can be called.' );
    $this->add_item( 'datetime', 'datetime', 'Date' );
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
    
    // this widget must have a parent, and it's subject must be a participant
    if( is_null( $this->parent ) || 'participant' != $this->parent->get_subject() )
      throw new exc\runtime(
        'Appointment widget must have a parent with participant as the subject.', __METHOD__ );

    $db_participant = new db\participant( $this->parent->get_record()->id );
    
    // determine the time difference
    $db_address = $db_participant->get_first_address();
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
      $note = sprintf( 'The participant\'s time zone is %s hours behind %s\'s time.',
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
    
    // create the min datetime array
    $start_qnaire_date = $this->parent->get_record()->start_qnaire_date;
    $datetime_limits = !is_null( $start_qnaire_date )
                     ? array( 'min_date' => substr( $start_qnaire_date, 0, -9 ) )
                     : NULL;

    // set the view's items
    $this->set_item( 'participant_id', $this->parent->get_record()->id );
    $this->set_item( 'phone_id', '', false, $phones );
    $this->set_item( 'datetime', '', true, $datetime_limits );

    $this->finish_setting_items();
  }
}
?>
