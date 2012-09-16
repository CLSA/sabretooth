<?php
/**
 * assignment_list.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui\widget;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * widget assignment list
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

    $operation_class_name = lib::get_class_name( 'database\operation' );

    // define whether or not voip spying is allowed
    $db_operation = $operation_class_name::get_operation( 'push', 'voip', 'spy' );
    $this->set_variable( 'allow_spy', lib::create( 'business\session' )->is_allowed( $db_operation ) );
    
    foreach( $this->get_record_list() as $record )
    {
      $db_user = $record->get_user();

      // get the status of the last phone call for this assignment
      $modifier = lib::create( 'database\modifier' );
      $modifier->order_desc( 'end_datetime' );
      $modifier->limit( 1 );
      $phone_call_list = $record->get_phone_call_list( $modifier );
      $status = 0 == count( $phone_call_list ) ? 'no calls made' : $phone_call_list[0]->status;
      if( 0 == strlen( $status ) ) $status = 'in progress';

      // determine whether we can spy on this assignment
      $allow_spy = false;
      $phone_call_mod = lib::create( 'database\modifier' );
      $phone_call_mod->where( 'end_datetime', '=', NULL );
      $open_call_count = $record->get_phone_call_count( $phone_call_mod );
      if( 0 < $open_call_count )
      { // if this assignment has an open call
        $voip_manager = lib::create( 'business\voip_manager' );
        if( $voip_manager->get_sip_enabled() && $voip_manager->get_call( $db_user ) )
        { // and if voip is enabled and the user has an active call
          $allow_spy = true;
        }
      }

      // assemble the row for this record
      $this->add_row( $record->id,
        array( 'user.name' => $db_user->name,
               'site.name' => $record->get_site()->name,
               'uid' => $record->get_interview()->get_participant()->uid,
               'calls' => $record->get_phone_call_count(),
               'start_datetime' => $record->start_datetime,
               'start_time' => $record->start_datetime,
               'end_time' => $record->end_datetime,
               'status' => $status,
               // allow_spy and user_id aren't columns, they are used for voip spying
               'allow_spy' => $allow_spy,
               'user_id' => $db_user->id,
               // note_count isn't a column, it's used for the note button
               'note_count' => $record->get_note_count() ) );
    }
  }

  /**
   * Overrides the parent class method to restrict by interview id, if necessary
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the list.
   * @return int
   * @access protected
   */
  public function determine_record_count( $modifier = NULL )
  {
    $interview_id = $this->get_argument( 'interview_id', NULL );

    if( !is_null( $interview_id ) )
    {
      if( is_null( $modifier ) ) $modifier = lib::create( 'database\modifier' );
      $modifier->where( 'interview_id', '=', $interview_id );
    }

    return parent::determine_record_count( $modifier );
  }

  /** 
   * Overrides the parent class method to restrict by interview id, if necessary
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the list.
   * @return array( record )
   * @access protected
   */
  public function determine_record_list( $modifier = NULL )
  {
    $interview_id = $this->get_argument( 'interview_id', NULL );

    if( !is_null( $interview_id ) )
    {
      if( is_null( $modifier ) ) $modifier = lib::create( 'database\modifier' );
      $modifier->where( 'interview_id', '=', $interview_id );
    }

    return parent::determine_record_list( $modifier );
  }
}
?>
