<?php
/**
 * ivr_appointment_add.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui\widget;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * widget ivr_appointment add
 */
class ivr_appointment_add extends base_appointment_view
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
    parent::__construct( 'ivr_appointment', 'add', $args );
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
    
    // this widget must have a parent, and it's subject must be a participant
    if( is_null( $this->parent ) || 'participant' != $this->parent->get_subject() )
      throw lib::create( 'exception\runtime',
        'IVR appointment widget must have a parent with participant as the subject.', __METHOD__ );

    $this->db_participant = lib::create( 'database\participant', $this->parent->get_record()->id );
    
    // add items to the view
    $this->add_item( 'participant_id', 'hidden' );
    $this->add_item( 'phone_id', 'enum', 'Phone Number',
      'Select which phone number to call from the IVR system.' );
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
    
    // create enum arrays
    $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'active', '=', true );
    $modifier->order( 'rank' );
    $phones = array();
    foreach( $this->db_participant->get_phone_list( $modifier ) as $db_phone )
      $phones[$db_phone->id] = $db_phone->rank.". ".$db_phone->number;
    
    $ivr_appointment_class_name = lib::get_class_name( 'database\ivr_appointment' );
    
    // create the min datetime array
    $start_qnaire_date = $this->parent->get_record()->get_start_qnaire_date();
    $datetime_limits = !is_null( $start_qnaire_date )
                     ? array( 'min_date' => $start_qnaire_date->format( 'Y-m-d' ) )
                     : NULL;

    // set the view's items
    $this->set_item( 'participant_id', $this->parent->get_record()->id );
    $this->set_item( 'phone_id', '', true, $phones );
    $this->set_item( 'datetime', '', true, $datetime_limits );
  }
}
