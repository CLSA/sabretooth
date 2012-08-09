<?php
/**
 * appointment_add.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui\widget;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * widget appointment add
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
    
    // add items to the view
    $this->add_item( 'participant_id', 'hidden' );
    $this->add_item( 'phone_id', 'enum', 'Phone Number',
      'Select a specific phone number to call for the appointment, or leave this field blank if '.
      'any of the participant\'s phone numbers can be called.' );
    $this->add_item( 'datetime', 'datetime', 'Date' );
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
    parent::setup();
    
    // this widget must have a parent, and it's subject must be a participant
    if( is_null( $this->parent ) || 'participant' != $this->parent->get_subject() )
      throw lib::create( 'exception\runtime',
        'Appointment widget must have a parent with participant as the subject.', __METHOD__ );

    $db_participant = lib::create( 'database\participant', $this->parent->get_record()->id );
    $this->set_variable( 'site_id', $db_participant->site_id );
    
    // determine the time difference
    $db_address = $db_participant->get_first_address();
    $time_diff = is_null( $db_address ) ? NULL : $db_address->get_time_diff();

    // need to add the participant's timezone information as information to the date item
    $session = lib::create( 'business\session' );
    $site_name = $session->get_site()->name;
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
    $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'active', '=', true );
    $modifier->order( 'rank' );
    $phones = array();
    foreach( $db_participant->get_phone_list( $modifier ) as $db_phone )
      $phones[$db_phone->id] = $db_phone->rank.". ".$db_phone->number;
    
    $appointment_class_name = lib::get_class_name( 'database\appointment' );
    $types = $appointment_class_name::get_enum_values( 'type' );
    $types = array_combine( $types, $types );
    
    // create the min datetime array
    $start_qnaire_date = $this->parent->get_record()->start_qnaire_date;
    $datetime_limits = !is_null( $start_qnaire_date )
                     ? array( 'min_date' => substr( $start_qnaire_date, 0, -9 ) )
                     : NULL;

    // set the view's items
    $this->set_item( 'participant_id', $this->parent->get_record()->id );
    $this->set_item( 'phone_id', '', false, $phones );
    $this->set_item( 'datetime', '', true, $datetime_limits );
    $this->set_item( 'type', key( $types ), true, $types );

    $this->set_variable( 'is_mid_tier', 2 == $session->get_role()->tier );
  }
}
?>
