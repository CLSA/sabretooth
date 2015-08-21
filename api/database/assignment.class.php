<?php
/**
 * assignment.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\database;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * assignment: record
 */
class assignment extends \cenozo\database\record
{
  /**
   * Overrides the parent save method.
   * @author Patrick Emond
   * @access public
   */
  public function save()
  {
    if( !is_null( $this->interview_id ) && is_null( $this->end_datetime ) )
    {
      // make sure there is a maximum of 1 unfinished assignment per user and interview
      $modifier = lib::create( 'database\modifier' );
      $modifier->where( 'interview_id', '=', $this->interview_id );
      $modifier->where( 'end_datetime', '=', NULL );
      if( 0 < static::count( $modifier ) )
        throw lib::create( 'exception\runtime',
          'Cannot have more than one active assignment per interview.', __METHOD__ );

      $modifier = lib::create( 'database\modifier' );
      $modifier->where( 'user_id', '=', $this->user_id );
      $modifier->where( 'end_datetime', '=', NULL );
      if( 0 < static::count( $modifier ) )
        throw lib::create( 'exception\runtime',
          'Cannot have more than one active assignment per user.', __METHOD__ );
    }

    parent::save();
  }

  /**
   * TODO: document
   */
  function has_open_phone_call()
  {
    // check the primary key value
    if( is_null( $this->id ) )
    {
      log::warning( 'Tried to determine if assignment with no id has an open phone_call.' );
      return NULL;
    }

    $phone_call_mod = lib::create( 'database\modifier' );
    $phone_call_mod->where( 'phone_call.end_datetime', '=', NULL );
    return 0 < $this->get_phone_call_count( $phone_call_mod );
  }

  /**
   * TODO: document
   */
  function get_open_phone_call()
  {
    // check the primary key value
    if( is_null( $this->id ) )
    {
      log::warning( 'Tried to get open phone_call from assignment with no id.' );
      return NULL;
    }

    $phone_call_mod = lib::create( 'database\modifier' );
    $phone_call_mod->where( 'phone_call.end_datetime', '=', NULL );
    $phone_call_mod->order_desc( 'phone_call.start_datetime' );
    $phone_call_list = $this->get_phone_call_object_list( $phone_call_mod );
    if( 1 < count( $phone_call_list ) )
      log::warning( sprintf( 'User %d (%s) has more than one open phone_call!', $this->id, $this->name ) );
    return 0 < count( $phone_call_list ) ? current( $phone_call_list ) : NULL;
  }
}
