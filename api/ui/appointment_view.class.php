<?php
/**
 * appointment_view.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui;
use sabretooth\log, sabretooth\util;
use sabretooth\business as bus;
use sabretooth\database as db;
use sabretooth\exception as exc;

/**
 * widget appointment view
 * 
 * @package sabretooth\ui
 */
class appointment_view extends base_view
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
    
    // add items to the view
    $this->add_item( 'contact_id', 'enum', 'Phone Number' );
    $this->add_item( 'date', 'datetime', 'Date' );
    $this->add_item( 'assignment.user', 'constant', 'Assigned to' );
    $this->add_item( 'assignment.start_time', 'constant', 'Start time' );
    $this->add_item( 'assignment.end_time', 'constant', 'End time' );
    $this->add_item( 'state', 'constant', 'State',
      '(One of upcoming, assignable, missed, assigned, in progress, complete or incomplete)' );
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
    
    // create enum arrays
    $db_participant = new db\participant( $this->get_record()->participant_id );
    $modifier = new db\modifier();
    $modifier->where( 'phone', '!=', NULL );
    $modifier->order( 'rank' );
    $contacts = array();
    foreach( $db_participant->get_contact_list( $modifier ) as $db_contact )
      $contacts[$db_contact->id] = $db_contact->rank.". ".$db_contact->phone;
    
    $db_assignment = $this->get_record()->get_assignment();

    // set the view's items
    $this->set_item( 'contact_id', $this->get_record()->contact_id, true, $contacts );
    $this->set_item( 'date', $this->get_record()->date, true );
    $this->set_item( 'assignment.user', $db_assignment->get_user()->name, false );
    $this->set_item( 'assignment.start_time',
      util::get_formatted_time( $db_assignment->start_time ), false );
    $this->set_item( 'assignment.end_time',
      util::get_formatted_time( $db_assignment->end_time ), false );
    $this->set_item( 'state', $this->get_record()->get_state(), false );

    $this->finish_setting_items();
  }
}
?>
