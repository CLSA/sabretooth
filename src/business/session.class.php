<?php
/**
 * session.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */

namespace sabretooth\business;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * Extends Cenozo's session class with custom functionality
 */
class session extends \cenozo\business\session
{
  /**
   * Extends parent method
   */
  public function shutdown()
  {
    // only shutdown after initialization
    if( !$this->is_initialized() ) return;

    // run any delayed repopulating of the queue
    $queue_class_name = lib::get_class_name( 'database\queue' );
    $queue_class_name::execute_delayed();

    parent::shutdown();
  }

  /**
   * Get the user's current assignment.
   * 
   * @return database\assignment
   * @throws exception\runtime
   * @access public
   */
  public function get_current_assignment()
  {
    // query for assignments which do not have a end time
    $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'end_datetime', '=', NULL );
    $modifier->order_desc( 'start_datetime' );
    $assignment_list = $this->get_user()->get_assignment_object_list( $modifier );

    // only one assignment should ever be open at a time, warn if this isn't the case
    if( 1 < count( $assignment_list ) )
      log::crit( sprintf( 'User "%s" has more than one active assignment!', $this->get_user()->name ) );

    return 1 <= count( $assignment_list ) ? current( $assignment_list ) : NULL;
  }

  /**
   * Get the user's current phone call.
   * 
   * @return database\phone_call
   * @throws exception\runtime
   * @access public
   */
  public function get_current_phone_call()
  {
    // without an assignment there can be no current call
    $db_assignment = $this->get_current_assignment();
    if( is_null( $db_assignment) ) return NULL;

    // query for phone calls which do not have a end time
    $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'end_datetime', '=', NULL );
    $modifier->order_desc( 'start_datetime' );
    $phone_call_list = $db_assignment->get_phone_call_object_list( $modifier );

    // only one phone call should ever be open at a time, warn if this isn't the case
    if( 1 < count( $phone_call_list ) )
      log::crit( sprintf( 'User "%s" has more than one active phone call!', $this->get_user()->name ) );

    return 1 <= count( $phone_call_list ) ? current( $phone_call_list ) : NULL;
  }
}
