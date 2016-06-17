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

    $db_role = lib::create( 'business\session' )->get_role();

    // remove all lists from the operator role
    if( 'operator' == $db_role->name ) foreach( $module_list as &$module ) $module['list_menu'] = false;

    // add child actions to certain modules
    if( array_key_exists( 'assignment', $module_list ) )
      $module_list['assignment']['children'] = array( 'phone_call' );
    if( array_key_exists( 'interview', $module_list ) )
      $module_list['interview']['children'] = array( 'assignment', 'appointment', 'callback' );
    if( array_key_exists( 'participant', $module_list ) )
    {
      array_unshift( $module_list['participant']['children'], 'interview' );

      // add extra query variables to history action
      $module_list['participant']['actions']['history'] .= '&{appointment}&{assignment}&{callback}';
    }
    if( array_key_exists( 'qnaire', $module_list ) )
    {
      $module_list['qnaire']['children'] = array( 'queue_state' );
      $module_list['qnaire']['choosing'] = array( 'event_type', 'quota' );
    }
    if( array_key_exists( 'queue', $module_list ) )
    {
      $module_list['queue']['list_menu'] = true; // always show the queue list
      $module_list['queue']['children'] = array( 'queue_state' );
      $module_list['queue']['choosing'] = array( 'participant' );

      // add special query parameters to queue-view
      if( array_key_exists( 'view', $module_list['queue']['actions'] ) )
        $module_list['queue']['actions']['view'] .= '?{restrict}&{order}&{reverse}';
    }
    if( array_key_exists( 'site', $module_list ) )
      array_unshift( $module_list['site']['children'], 'queue_state' );
    if( array_key_exists( 'state', $module_list ) )
    {
      // remove the state list from the operator+ role
      if( 'operator+' == $db_role->name ) $module_list['state']['list_menu'] = false;
    }
    if( array_key_exists( 'user', $module_list ) )
    {
      // remove the state list from the operator+ role
      if( 'operator+' == $db_role->name ) $module_list['user']['list_menu'] = false;

      // remove the user view action from operator roles (it is for viewing personal calendar only)
      if( 'operator' == $db_role->name || 'operator+' == $db_role->name )
      {
        unset( $module_list['user']['actions']['list'] );
        unset( $module_list['user']['actions']['view'] );
      }

      // add calendar to user actions
      if( in_array( $db_role->name, array( 'helpline', 'operator', 'operator+', 'supervisor' ) ) )
        $module_list['user']['actions']['calendar'] = '/{identifier}';
    }

    return $module_list;
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

    $session = lib::create( 'business\session' );
    $db_site = $session->get_site();
    $db_role = $session->get_role();
    $db_user = $session->get_user();

    // operators get no list items
    if( 'operator' == $db_role->name )
    {
      unset( $list['Participant Search'] );
      $list['Assignment Control'] = array(
        'subject' => 'assignment',
        'action' => 'control',
        'query' => '?{restrict}&{order}&{reverse}' );
    }

    if( in_array( $db_role->name, array( 'operator', 'operator+' ) ) )
    {
      $list['Personal Calendar'] = array(
        'subject' => 'user',
        'action' => 'calendar',
        'query' => '/{identifier}',
        'values' => sprintf( '{identifier:"name=%s"}', $db_user->name ) );
    }

    // add application-specific states to the base list
    if( in_array( $db_role->name, array( 'helpline', 'operator+', 'supervisor' ) ) )
    {
      $list['Assignment Control'] = array(
        'subject' => 'assignment',
        'action' => 'control',
        'query' => '?{restrict}&{order}&{reverse}' );
    }

    if( 2 <= $db_role->tier )
    {
      $query = '?{qnaire}&{language}';
      if( $db_role->all_sites ) $query .= '&{site}';
      $list['Queue Tree'] = array(
        'subject' => 'queue',
        'action' => 'tree',
        'query' => $query );
    }

    if( !$db_role->all_sites && 1 < $db_role->tier )
    {
      $list['Site Details'] = array(
        'subject' => 'site',
        'action' => 'view',
        'query' => '/{identifier}',
        'values' => sprintf( '{identifier:"name=%s"}', $db_site->name ) );
    }

    if( 'operator' != $db_role->name )
    {
      if( !$db_role->all_sites || 'helpline' == $db_role->name )
      {
        $list['Appointment Calendar'] = array(
          'subject' => 'appointment',
          'action' => 'calendar',
          'query' => '/{identifier}',
          'values' => sprintf( '{identifier:"name=%s"}', $db_site->name ) );
        $list['Availability Calendar'] = array(
          'subject' => 'availability',
          'action' => 'calendar',
          'query' => '/{identifier}',
          'values' => sprintf( '{identifier:"name=%s"}', $db_site->name ) );
        $list['Callback Calendar'] = array(
          'subject' => 'callback',
          'action' => 'calendar',
          'query' => '/{identifier}',
          'values' => sprintf( '{identifier:"name=%s"}', $db_site->name ) );
        $list['Capacity Calendar'] = array(
          'subject' => 'capacity',
          'action' => 'calendar',
          'query' => '/{identifier}',
          'values' => sprintf( '{identifier:"name=%s"}', $db_site->name ) );

        if( 1 < $db_role->tier )
        {
          $list['Shift Calendar'] = array(
            'subject' => 'shift',
            'action' => 'calendar',
            'query' => '/{identifier}',
            'values' => sprintf( '{identifier:"name=%s"}', $db_site->name ) );
          $list['Shift Template Calendar'] = array(
            'subject' => 'shift_template',
            'action' => 'calendar',
            'query' => '/{identifier}',
            'values' => sprintf( '{identifier:"name=%s"}', $db_site->name ) );
        }
      }
    }

    return $list;
  }

  /**
   * Extends the parent method
   */
  protected function get_report_items()
  {
    $list = parent::get_report_items();
    $db_role = lib::create( 'business\session' )->get_role();

    // operators get no list items
    if( 'operator' == $db_role->name ) $list = array();

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
