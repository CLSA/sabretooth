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
  protected function build_module_list()
  {
    parent::build_module_list();

    $db_role = lib::create( 'business\session' )->get_role();

    // remove all lists from the operator role
    if( 'operator' == $db_role->name ) $this->set_all_list_menu( false );

    // add child actions to certain modules

    $module = $this->assert_module( 'capacity' );
    $module->add_action( 'calendar', '/{identifier}' );
    
    $module = $this->get_module( 'interview' );
    if( !is_null( $module ) ) $module->add_child( 'appointment', 0 );

    $module = $this->get_module( 'participant' );
    if( !is_null( $module ) ) $module->append_action_query( 'history', '&{appointment}' );

    $module = $this->get_module( 'qnaire' );
    if( !is_null( $module ) )
    {
      $module->add_choose( 'site' );
      $module->add_choose( 'quota' );
    }

    $module = $this->get_module( 'queue' );
    if( !is_null( $module ) )
    {
      $module->set_list_menu( true ); // always show the queue list
      $module->add_choose( 'participant' );

      // add special query parameters to queue-view
      $module->append_action_query( 'view', '?{restrict}&{order}&{reverse}' );
    }

    $module = $this->get_module( 'quota' );
    if( !is_null( $module ) ) $module->add_choose( 'qnaire' );

    $module = $this->get_module( 'site' );
    if( !is_null( $module ) ) $module->add_choose( 'qnaire' );

    // remove the state list from the operator+ role
    $module = $this->get_module( 'state' );
    if( !is_null( $module ) && 'operator+' == $db_role->name ) $module->set_list_menu( false );

    $module = $this->get_module( 'user' );
    if( !is_null( $module ) )
    {
      // remove the state list from the operator+ role
      if( 'operator+' == $db_role->name ) $module->set_list_menu( false );

      // remove the user view action from operator roles (it is for viewing personal calendar only)
      if( 'operator' == $db_role->name || 'operator+' == $db_role->name )
      {
        $module->remove_action( 'list' );
        $module->remove_action( 'view' );
      }

      // add calendar to user actions
      if( in_array( $db_role->name, array( 'helpline', 'operator', 'operator+', 'supervisor' ) ) )
        $module->add_action( 'calendar', '/{identifier}' );
    }
  }

  /**
   * Extends the parent method
   */
  protected function build_listitem_list()
  {
    parent::build_listitem_list();

    // add application-specific states to the base list
    $this->add_listitem( 'Questionnaires', 'qnaire' );
    $this->add_listitem( 'Queues', 'queue' );
    $this->add_listitem( 'Vacancies', 'vacancy' );
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
    if( in_array( $db_role->name, array( 'helpline', 'operator', 'operator+', 'supervisor' ) ) )
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

    if( !$db_role->all_sites || 'helpline' == $db_role->name )
    {
      $list['Appointment Calendar'] = array(
        'subject' => 'appointment',
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
        $list['Vacancy Calendar'] = array(
          'subject' => 'vacancy',
          'action' => 'calendar',
          'query' => '/{identifier}',
          'values' => sprintf( '{identifier:"name=%s"}', $db_site->name ) );
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

    return $list;
  }
}
