<?php
/**
 * user.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\database;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * user: record
 */
class user extends \cenozo\database\user
{
  /**
   * TODO: document
   */
  function has_open_assignment()
  {
    // check the primary key value
    if( is_null( $this->id ) )
    {
      log::warning( 'Tried to determine if user with no primary key has an open assignment.' );
      return NULL;
    }

    $assignment_mod = lib::create( 'database\modifier' );
    $assignment_mod->where( 'end_datetime', '=', NULL );
    return 0 < $this->get_assignment_count( $assignment_mod );
  }

  /**
   * TODO: document
   */
  function get_open_assignment()
  {
    // check the primary key value
    if( is_null( $this->id ) )
    {
      log::warning( 'Tried to get open assignment from user with no primary key.' );
      return NULL;
    }

    $assignment_mod = lib::create( 'database\modifier' );
    $assignment_mod->where( 'end_datetime', '=', NULL );
    $assignment_mod->order_desc( 'start_datetime' );
    $assignment_list = $this->get_assignment_object_list( $assignment_mod );
    if( 1 < count( $assignment_list ) )
      log::warning( sprintf( 'User %d (%s) has more than one open assignment!', $this->id, $this->name ) );
    return 0 < count( $assignment_list ) ? current( $assignment_list ) : NULL;
  }

  /**
   * TODO: document
   */
  function has_open_phone_call()
  {
    // check the primary key value
    if( is_null( $this->id ) )
    {
      log::warning( 'Tried to determine if user with no primary key has an open phone call.' );
      return NULL;
    }

    $assignment_mod = lib::create( 'database\modifier' );
    $assignment_mod->join( 'phone_call', 'assignment.id', 'phone_call.assignment_id' );
    $assignment_mod->where( 'phone_call.end_datetime', '=', NULL );
    return 0 < $this->get_assignment_count( $assignment_mod );
  }

  /**
   * TODO: document
   */
  function get_open_phone_call()
  {
    // check the primary key value
    if( is_null( $this->id ) )
    {
      log::warning( 'Tried to get open phone_call from user with no primary key.' );
      return NULL;
    }

    $phone_call_class_name = lib::get_class_name( 'database\phone_call' );
    $phone_call_mod = lib::create( 'database\modifier' );
    $phone_call_mod->join( 'assignment', 'phone_call.assignment_id', 'assignment.id' );
    $phone_call_mod->where( 'assignment.user_id', '=', $this->id );
    $phone_call_mod->where( 'phone_call.end_datetime', '=', NULL );
    $phone_call_mod->order_desc( 'phone_call.start_datetime' );
    $phone_call_list = $phone_call_class_name::select_objects( $phone_call_mod );
    if( 1 < count( $phone_call_list ) )
      log::warning( sprintf( 'User %d (%s) has more than one open phone call!', $this->id, $this->name ) );
    return 0 < count( $phone_call_list ) ? current( $phone_call_list ) : NULL;
  }
}
