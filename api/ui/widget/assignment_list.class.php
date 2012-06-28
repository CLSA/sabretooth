<?php
/**
 * assignment_list.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui\widget;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * widget assignment list
 * 
 * @package sabretooth\ui
 */
class assignment_list extends site_restricted_list
{
  /**
   * Constructor
   * 
   * Defines all variables required by the assignment list.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'assignment', $args );
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
    
    $interview_id = $this->get_argument( 'interview_id', NULL );

    $this->add_column( 'user.name', 'string', 'Operator', true );
    $this->add_column( 'site.name', 'string', 'Site', true );
    // only add the participant column if we are not restricting by interview
    if( is_null( $interview_id ) ) $this->add_column( 'uid', 'string', 'UID' );
    $this->add_column( 'calls', 'number', 'Calls' );
    $this->add_column( 'start_datetime', 'date', 'Date', true );
    $this->add_column( 'start_time', 'time', 'Start Time' );
    $this->add_column( 'end_time', 'time', 'End Time' );
    $this->add_column( 'status', 'string', 'Status' );
  }
  
  /**
   * Set the rows array needed by the template.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access protected
   */
  protected function setup()
  {
    parent::setup();
    
    foreach( $this->get_record_list() as $record )
    {
      // get the status of the last phone call for this assignment
      $modifier = lib::create( 'database\modifier' );
      $modifier->order_desc( 'end_datetime' );
      $modifier->limit( 1 );
      $phone_call_list = $record->get_phone_call_list( $modifier );
      $status = 0 == count( $phone_call_list ) ? 'no calls made' : $phone_call_list[0]->status;
      if( 0 == strlen( $status ) ) $status = 'in progress';

      // assemble the row for this record
      $this->add_row( $record->id,
        array( 'user.name' => $record->get_user()->name,
               'site.name' => $record->get_site()->name,
               'uid' => $record->get_interview()->get_participant()->uid,
               'calls' => $record->get_phone_call_count(),
               'start_datetime' => $record->start_datetime,
               'start_time' => $record->start_datetime,
               'end_time' => $record->end_datetime,
               'status' => $status,
               // note_count isn't a column, it's used for the note button
               'note_count' => $record->get_note_count() ) );
    }
  }
}
?>
