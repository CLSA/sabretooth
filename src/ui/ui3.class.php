<?php
/**
 * ui.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */

namespace sabretooth\ui;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * Application extension to ui class
 */
class ui3 extends \cenozo\ui\ui3
{
  /**
   * Extends the parent method
   */
  public static function generate()
  {
    $data = parent::generate();

    $session = lib::create( 'business\session' );
    $db_site = $session->get_site();
    $db_role = $session->get_role();
    $db_user = $session->get_user();

    if( 'operator' == $db_role->name ) $data['menu']['lists'] = [];

    if( array_key_exists( 'appointment', $data['module_list'] ) )
    {
      $module = $data['module_list']['appointment'];
      $module->add_action( 'calendar', '/{identifier}?{calendar}' );
    }

    if( array_key_exists( 'interview', $data['module_list'] ) )
    {
      $module = $data['module_list']['interview'];
      $module->add_child( 'appointment' );
    }

    if( array_key_exists( 'overview', $data['module_list'] ) )
    {
      $module = $data['module_list']['overview'];
      // add the study parameter (used by the progress review only)
      $module->append_action_query( 'view', '?{study}' );
    }

    if( array_key_exists( 'participant', $data['module_list'] ) )
    {
      $module = $data['module_list']['participant'];
      $module->append_action_query( 'history', '&{appointment}' );
    }

    if( array_key_exists( 'qnaire', $data['module_list'] ) )
    {
      $module = $data['module_list']['qnaire'];
      $module->add_choose( 'collection' );
      $module->add_choose( 'hold_type' );
      $module->add_choose( 'site' );
      $module->add_choose( 'stratum' );
      $module->add_choose( 'alternate_type' );
      $module->add_action( 'mass_method', '/{identifier}' );
    }

    if( array_key_exists( 'queue', $data['module_list'] ) )
    {
      $module = $data['module_list']['queue'];
      $module->set_list_menu( true ); // always show the queue list
      $module->add_choose( 'participant' );
      // add special query parameters to queue-view
      $module->append_action_query( 'view', '?{restrict}&{order}&{reverse}' );
    }

    if( array_key_exists( 'stratum', $data['module_list'] ) )
    {
      $module = $data['module_list']['stratum'];
      $module->add_choose( 'qnaire' );
    }

    if( array_key_exists( 'site', $data['module_list'] ) )
    {
      $module = $data['module_list']['site'];
      $module->add_child( 'appointment_mail' );
      $module->add_choose( 'qnaire' );
    }

    // remove the hold_type list from the operator+ role
    if( 'operator+' == $db_role->name )
    {
      if( array_key_exists( 'hold_type', $data['module_list'] ) )
      {
        $module = $data['module_list']['hold_type'];
        $module->set_list_menu( false );
      }

      // remove the trace_type list from the operator+ role
      if( array_key_exists( 'trace_type', $data['module_list'] ) )
      {
        $module = $data['module_list']['trace_type'];
        $module->set_list_menu( false );
      }

      // remove the proxy_type list from the operator+ role
      if( array_key_exists( 'proxy_type', $data['module_list'] ) )
      {
        $module = $data['module_list']['proxy_type'];
        $module->set_list_menu( false );
      }
    }

    if( array_key_exists( 'user', $data['module_list'] ) )
    {
      $module = $data['module_list']['user'];
      // remove the user list from the operator+ role
      if( 'operator+' == $db_role->name ) $module->set_list_menu( false );

      // remove the user view action from operator roles (it is for viewing personal calendar only)
      if( 'operator' == $db_role->name || 'operator+' == $db_role->name )
      {
        $module->remove_action( 'list' );
        $module->remove_action( 'view' );
      }

      // add calendar to user actions
      if( in_array( $db_role->name, [ 'helpline', 'operator', 'operator+', 'supervisor' ] ) )
        $module->add_action( 'calendar', '/{identifier}?{calendar}' );
    }

    if( array_key_exists( 'vacancy', $data['module_list'] ) )
    {
      $module = $data['module_list']['vacancy'];
      $module->add_action( 'calendar', '/{identifier}?{calendar}' );
      $module->add_child( 'appointment' );
    }

    $menu_list_items = [
      ['subject' => 'qnaire', 'title' => 'Questionnaires'],
      ['subject' => 'queue', 'title' => 'Queues'],
      ['subject' => 'vacancy', 'title' => 'Vacancies'],
    ];

    foreach( $menu_list_items as $item )
    {
      if( array_key_exists( $item['subject'], $data['module_list'] ) )
      {
        $module = $data['module_list'][$item['subject']];
        if( $module->get_list_menu() && $module->has_action( 'list' ) )
          $data['menu']['lists'][$item['title']] = $item['subject'];
      }
    }

    if( 'operator' == $db_role->name )
    {
      unset( $data['menu']['utilities']['Participant Search'] );
    }

    if( in_array( $db_role->name, [ 'operator', 'operator+' ] ) ) 
    {   
      $data['menu']['utilities']['Personal Calendar'] = [
        'subject' => 'user',
        'action' => sprintf( 'calendar/name=%s', $db_user->name ),
        'query' => '/{identifier}?{calendar}'
      ];
    }   

    // add application-specific lists to the base list
    if( in_array( $db_role->name, [ 'helpline', 'operator', 'operator+', 'supervisor' ] ) ) 
    {   
      $data['menu']['utilities']['Assignment Control'] = [
        'subject' => 'assignment',
        'action' => 'control',
        'query' => '?{restrict}&{order}&{reverse}'
      ];
    }   

    if( 2 <= $db_role->tier )
    {   
      $query = '?{qnaire}&{language}';
      if( $db_role->all_sites ) $query .= '&{site}';
      $data['menu']['utilities']['Queue Tree'] = [
        'subject' => 'queue',
        'action' => 'tree',
        'query' => $query
      ];
    }   

    if( !$db_role->all_sites && 1 < $db_role->tier )
    {   
      $data['menu']['utilities']['Site Details'] = [
        'subject' => 'site',
        'action' => 'view',
        'action' => sprintf( 'view/name=%s', $db_site->name ),
        'query' => '/{identifier}',
      ];
    }   

    if( !$db_role->all_sites || 'helpline' == $db_role->name )
    {   
      $data['menu']['utilities']['Appointment Calendar'] = [
        'subject' => 'appointment',
        'action' => sprintf( 'calendar/name=%s', $db_site->name ),
        'query' => '/{identifier}?{calendar}',
      ];

      if( 1 < $db_role->tier )
      {   
        $data['menu']['utilities']['Vacancy Calendar'] = [
          'subject' => 'vacancy',
          'action' => sprintf( 'calendar/name=%s', $db_site->name ),
          'query' => '/{identifier}?{calendar}',
        ];
      }
    }

    return $data;
  }
}
