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
      $module_list['interview']['children'] = array( 'assignment' );
    if( array_key_exists( 'opal_instance', $module_list ) )
      $module_list['opal_instance']['children'] = array( 'activity' );
    if( array_key_exists( 'participant', $module_list ) )
      array_unshift( $module_list['participant']['children'], 'interview' );
    if( array_key_exists( 'qnaire', $module_list ) )
      $module_list['qnaire']['children'] = array( 'phase' );

    return $module_list;
  }

  /**
   * Extends the parent method
   */
  protected function get_operation_items()
  {
    $operation_items = parent::get_operation_items();
    if( 'operator' == lib::create( 'business\session' )->get_role()->name )
      array_unshift( $operation_items, 'break' );

    return $operation_items;
  }

  /**
   * Extends the parent method
   */
  protected function get_list_items()
  {
    $list = parent::get_list_items();
    
    // add application-specific states to the base list
    $list['cedar_instance'] = 'Cedar Instances';
    $list['interview'] = 'Interviews';
    $list['opal_instance'] = 'Opal Instances';
    $list['qnaire'] = 'Questionnaires';
    $list['queue'] = 'Queues';

    return $list;
  }
}
