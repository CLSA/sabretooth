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
    if( array_key_exists( 'cedar_instance', $module_list ) )
      $module_list['cedar_instance']['children'] = array( 'activity' );
    if( array_key_exists( 'interview', $module_list ) ) 
      $module_list['interview']['children'] = array( 'assignment', 'appointment', 'callback' );
    if( array_key_exists( 'opal_instance', $module_list ) )
      $module_list['opal_instance']['children'] = array( 'activity' );
    if( array_key_exists( 'participant', $module_list ) )
      array_unshift( $module_list['participant']['children'], 'interview' );
    if( array_key_exists( 'qnaire', $module_list ) )
    {
      $module_list['qnaire']['children'] = array( 'phase', 'queue_state' );
      $module_list['qnaire']['choosing'] = array( 'event_type', 'interview_method', 'quota' );
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
    else if( 'supervisor' == $role )
      array_splice( $operation_items, array_search( 'account', $operation_items ) + 1, 0, 'siteSettings' );

    return $operation_items;
  }

  /**
   * Extends the parent method
   */
  protected function get_list_items()
  {
    $list = parent::get_list_items();
    $db_role = lib::create( 'business\session' )->get_role();
    
    // add application-specific states to the base list
    $list['Interviews'] = 'interview';
    $list['Queues']     = 'queue';

    if( 3 <= $db_role->tier )
    {
      $list['Cedar Instances'] = 'cedar_instance';
      $list['Opal Instances']  = 'opal_instance';
      $list['Questionnaires']  = 'qnaire';
    }

    return $list;
  }

  /**
   * Extends the parent method
   */
  protected function get_utility_items()
  {
    $list = parent::get_utility_items();
    $db_role = lib::create( 'business\session' )->get_role();
    
    // add application-specific states to the base list
    if( 2 <= $db_role->tier )
      $list['Queue Tree'] = array( 'subject' => 'queue', 'action' => 'tree' );

    return $list;
  }
}
