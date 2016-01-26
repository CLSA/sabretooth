<?php
/**
 * ui.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * Application extension to ui class
 */
class ui extends \cenozo\ui\ui
{
  /**
   * Extends the parent method
   */
  protected function get_module_list( $modifier = NULL )
  {
    $module_list = parent::get_module_list( $modifier );

    // add child actions to certain modules
    if( array_key_exists( 'assignment', $module_list ) )
      $module_list['assignment']['children'] = array( 'phone_call' );
    if( array_key_exists( 'interview', $module_list ) ) 
      $module_list['interview']['children'] = array( 'assignment', 'appointment', 'callback' );
    if( array_key_exists( 'opal_instance', $module_list ) )
      $module_list['opal_instance']['children'] = array( 'activity' );
    if( array_key_exists( 'participant', $module_list ) )
      array_unshift( $module_list['participant']['children'], 'interview' );
    if( array_key_exists( 'qnaire', $module_list ) )
    {
      $module_list['qnaire']['children'] = array( 'queue_state' );
      $module_list['qnaire']['choosing'] = array( 'event_type', 'quota' );
    }
    if( array_key_exists( 'queue', $module_list ) )
    {
      $module_list['queue']['children'] = array( 'queue_state' );
      $module_list['queue']['choosing'] = array( 'participant' );
    }
    if( array_key_exists( 'site', $module_list ) )
      array_unshift( $module_list['site']['children'], 'queue_state' );

    return $module_list;
  }

  /**
   * Extends the parent method
   */
  protected function get_operation_items()
  {
    $role = lib::create( 'business\session' )->get_role()->name;

    $operation_items = parent::get_operation_items();
    if( 'operator' == $role ) array_unshift( $operation_items, 'break' );

    return $operation_items;
  }

  /**
   * Extends the parent method
   */
  protected function get_list_items( $module_list )
  {
    $list = parent::get_list_items( $module_list );
    $db_role = lib::create( 'business\session' )->get_role();
    
    // add application-specific states to the base list
    if( array_key_exists( 'interview', $module_list ) && $module_list['interview']['list_menu'] )
      $list['Interviews'] = 'interview';
    if( array_key_exists( 'opal_instance', $module_list ) && $module_list['opal_instance']['list_menu'] )
      $list['Opal Instances'] = 'opal_instance';
    if( array_key_exists( 'qnaire', $module_list ) && $module_list['qnaire']['list_menu'] )
      $list['Questionnaires'] = 'qnaire';
    if( array_key_exists( 'queue', $module_list ) && $module_list['queue']['list_menu'] )
      $list['Queues'] = 'queue';
    if( array_key_exists( 'shift', $module_list ) && $module_list['shift']['list_menu'] )
      $list['Shifts'] = 'shift';
    if( array_key_exists( 'shift_template', $module_list ) && $module_list['shift_template']['list_menu'] )
      $list['Shift Templates'] = 'shift_template';

    return $list;
  }

  /**
   * Extends the parent method
   */
  protected function get_utility_items()
  {
    $list = parent::get_utility_items();
    $db_site = lib::create( 'business\session' )->get_site();
    $db_role = lib::create( 'business\session' )->get_role();
    
    // add application-specific states to the base list
    if( in_array( $db_role->name, array( 'helpline', 'operator', 'supervisor' ) ) )
      $list['Assignment Home'] = array( 'subject' => 'assignment', 'action' => 'home' );
    if( 2 <= $db_role->tier )
      $list['Queue Tree'] = array( 'subject' => 'queue', 'action' => 'tree' );
    if( !$db_role->all_sites && 1 < $db_role->tier )
    {
      $list['Site Details'] = array(
        'subject' => 'site',
        'action' => 'view',
        'identifier' => sprintf( 'name=%s', $db_site->name ) );
    }
    if( !$db_role->all_sites || 'helpline' == $db_role->name )
    {
      $list['Appointment Calendar'] = array( 'subject' => 'appointment', 'action' => 'calendar' );
      $list['Availability Calendar'] = array( 'subject' => 'availability', 'action' => 'calendar' );
      $list['Capacity Calendar'] = array( 'subject' => 'capacity', 'action' => 'calendar' );

      if( 1 < $db_role->tier )
      {
        $list['Shift Calendar'] = array( 'subject' => 'shift', 'action' => 'calendar' );
        $list['Shift Template Calendar'] = array( 'subject' => 'shift_template', 'action' => 'calendar' );
      }
    }

    return $list;
  }

  /**
   * Extends the parent method
   */
  protected function get_auxiliary_items()
  {
    $list = parent::get_auxiliary_items();

    $db_role = lib::create( 'business\session' )->get_role();

    // the availability and capacity calenders need one another
    $list[] = 'availability';
    $list[] = 'capacity';

    return $list;
  }
}
