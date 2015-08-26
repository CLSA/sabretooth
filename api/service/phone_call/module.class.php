<?php
/**
 * module.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\service\phone_call;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * Performs operations which effect how this module is used in a service
 */
class module extends \cenozo\service\module
{
  /**
   * Extend parent method
   */
  public function validate()
  {
    parent::validate();

    $service_class_name = lib::get_class_name( 'service\service' );
    $db_user = lib::create( 'business\session' )->get_user();
    $db_role = lib::create( 'business\session' )->get_role();

    $method = $this->get_method();
    if( 'PATCH' == $method &&
        $this->get_argument( 'close', false ) &&
        !array_key_exists( 'status', $this->get_file_as_array() ) )
    {
      // can't close a phone call without defining the status
      $this->set_data( 'Cannot close a phone call without specifying the status.' );
      $this->get_status()->set_code( 400 );
    }
    else if( ( 'DELETE' == $method || 'PATCH' == $method ) &&
             3 > $db_role->tier &&
             $this->get_resource()->get_assignment()->user_id != $db_user->id )
    {
      // only admins can delete or modify phone calls other than their own
      $this->get_status()->set_code( 403 );
    }
    else if( 'POST' == $method )
    {
      // do not allow more than one open phone_call
      $data = NULL;

      if( !$db_user->has_open_assignment() )
        $data = 'Cannot create a new phone call since there is no open assignment.';
      else if( $db_user->has_open_phone_call() )
        $data = 'Cannot create a new phone call since you already have one open.';
      
      if( !is_null( $data ) )
      {
        $this->set_data( $data );
        $this->get_status()->set_code( 409 );
      }
    }
  }

  /**
   * Extend parent method
   */
  public function prepare_read( $select, $modifier )
  {
    parent::prepare_read( $select, $modifier );

    $session = lib::create( 'business\session' );

    // restrict to participants in this site (for some roles)
    if( !$session->get_role()->all_sites )
    {
      $modifier->join( 'assignment', 'phone_call.assignment_id', 'assignment.id' );
      $modifier->where( 'assignment.site_id', '=', $session->get_site()->id );
    }

    if( $select->has_table_columns( 'phone' ) )
      $modifier->join( 'phone', 'phone_call.phone_id', 'phone.id' );
  }

  /**
   * Extend parent method
   */
  public function pre_write( $record )
  {
    parent::pre_write( $record );

    $method = $this->get_method();
    if( 'POST' == $method && $this->get_argument( 'open', false ) )
    {
      $session = lib::create( 'business\session' );
      $db_user = $session->get_user();

      $post_object = $this->get_file_as_object();
      $record->assignment_id = $db_user->get_open_assignment()->id;
      $record->phone_id = $post_object->phone_id;
      $record->start_datetime = util::get_datetime_object()->format( 'Y-m-d H:i:s' );
    }
    else if( 'PATCH' == $method )
    {
      if( $this->get_argument( 'close', false ) )
      { // close the phone call by setting the end datetime
        if( !is_null( $record->end_datetime ) )
        {
          log::warning( sprintf( 'Tried to close phone call id %d which is already closed.', $record->id ) );
        }
        else
        {
          $record->end_datetime = util::get_datetime_object()->format( 'Y-m-d H:i:s' );
        }
      }
    }
  }
}
