<?php
/**
 * overview.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */

namespace sabretooth\business\overview;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * overview: progress
 */
class progress extends \cenozo\business\overview\base_overview
{
  /**
   * Implements abstract method
   */
  protected function build( $modifier = NULL )
  {
    $phone_call_class_name = lib::get_class_name( 'database\phone_call' );
    $qnaire_class_name = lib::get_class_name( 'database\qnaire' );
    $hold_type_class_name = lib::get_class_name( 'database\hold_type' );
    $trace_type_class_name = lib::get_class_name( 'database\trace_type' );
    $proxy_type_class_name = lib::get_class_name( 'database\proxy_type' );

    $session = lib::create( 'business\session' );
    $db = $session->get_database();
    $db_application = $session->get_application();
    $db_site = $session->get_site();
    $db_role = $session->get_role();
    $db_user = $session->get_user();

    $data = array();

    // get a list of all hold types
    $hold_type_mod = lib::create( 'database\modifier' );
    $hold_type_mod->order( 'type' );
    $hold_type_mod->order( 'name' );
    $hold_type_list = array();
    foreach( $hold_type_class_name::select_objects( $hold_type_mod ) as $db_hold_type )
      $hold_type_list[] = $db_hold_type->to_string();

    // get a list of all trace types
    $trace_type_mod = lib::create( 'database\modifier' );
    $trace_type_mod->order( 'name' );
    $trace_type_list = array();
    foreach( $trace_type_class_name::select_objects( $trace_type_mod ) as $db_trace_type )
      $trace_type_list[] = $db_trace_type->name;

    // get a list of all proxy types
    $proxy_type_mod = lib::create( 'database\modifier' );
    $proxy_type_mod->order( 'name' );
    $proxy_type_list = array();
    foreach( $proxy_type_class_name::select_objects( $proxy_type_mod ) as $db_proxy_type )
      $proxy_type_list[] = $db_proxy_type->name;

    // get a list of all qnaires
    $qnaire_sel = lib::create( 'database\select' );
    $qnaire_sel->add_table_column( 'script', 'name' );
    $qnaire_mod = lib::create( 'database\modifier' );
    $qnaire_mod->join( 'script', 'qnaire.script_id', 'script.id' );
    $qnaire_mod->order( 'script.name' );
    $qnaire_list = array();
    foreach( $qnaire_class_name::select( $qnaire_sel, $qnaire_mod ) as $qnaire ) $qnaire_list[] = $qnaire['name'];

    // get a list of all call statuses
    $call_status_list = $phone_call_class_name::get_enum_values( 'status' );

    // create generic select and modifier objects which can be re-used
    $select = lib::create( 'database\select' );
    $select->from( 'queue_has_participant' );
    $select->add_table_column( 'site', 'IFNULL( site.name, "(none)" )', 'site', false );
    $select->add_column( 'COUNT(*)', 'total', false );

    // we need to add the input modifier's statements at the end, so rename it and merge it later
    $input_modifier = $modifier;

    $modifier = lib::create( 'database\modifier' );
    $modifier->join( 'queue', 'queue_has_participant.queue_id', 'queue.id' );
    $modifier->left_join( 'site', 'queue_has_participant.site_id', 'site.id' );
    $modifier->left_join( 'qnaire', 'queue_has_participant.qnaire_id', 'qnaire.id' );
    $modifier->left_join( 'script', 'qnaire.script_id', 'script.id' );
    if( !$db_role->all_sites ) $modifier->where( 'site.id', '=', $db_site->id );
    $modifier->group( 'queue_has_participant.site_id' );

    if( !is_null( $input_modifier ) )
    {
      $modifier->join(
        'study_has_participant',
        'queue_has_participant.participant_id',
        'study_has_participant.participant_id'
      );
      $modifier->join(
        'study',
        'study_has_participant.study_id',
        'study.id'
      );
      $modifier->merge( $input_modifier );
    }

    // start with the participant totals
    /////////////////////////////////////////////////////////////////////////////////////////////
    $cat_mod = clone $modifier;
    $cat_mod->where( 'queue.name', '=', 'all' );

    foreach( $db->get_all( sprintf( '%s %s', $select->get_sql(), $cat_mod->get_sql() ) ) as $row )
    {
      $node = $this->add_root_item( $row['site'] );
      $this->add_item( $node, 'All Participants', $row['total'] );
      $this->add_item( $node, 'Not Enrolled', 0 );
      $hold_type_node = $this->add_item( $node, 'Hold Types' );
      foreach( $hold_type_list as $hold_type ) $this->add_item( $hold_type_node, $hold_type, 0 );
      $trace_type_node = $this->add_item( $node, 'Trace Types' );
      foreach( $trace_type_list as $trace_type ) $this->add_item( $trace_type_node, $trace_type, 0 );
      $proxy_type_node = $this->add_item( $node, 'Proxy Types' );
      foreach( $proxy_type_list as $proxy_type ) $this->add_item( $proxy_type_node, $proxy_type, 0 );
      foreach( $qnaire_list as $qnaire )
      {
        $qnaire_node = $this->add_item( $node, $qnaire.' Interview' );
        $this->add_item( $qnaire_node, 'Not yet called', 0 );
        $this->add_item( $qnaire_node, 'Call in Progress', 0 );
        foreach( $call_status_list as $call_status ) $this->add_item( $qnaire_node, ucWords( $call_status ), 0 );
        $this->add_item( $qnaire_node, 'Phone Version Completed', 0 );
        $this->add_item( $qnaire_node, 'Web Version in Progress', 0 );
        $this->add_item( $qnaire_node, 'Web Version Completed', 0 );
        $this->add_item( $qnaire_node, 'Completed Interviews', 0 );
      }
      $site_node_lookup[$row['site']] = $node;
    }

    // not enrolled participants
    /////////////////////////////////////////////////////////////////////////////////////////////
    $cat_mod = clone $modifier;
    $cat_mod->where( 'queue.name', '=', 'not enrolled' );

    foreach( $db->get_all( sprintf( '%s %s', $select->get_sql(), $cat_mod->get_sql() ) ) as $row )
    {
      $node = $site_node_lookup[$row['site']]->find_node( 'Not Enrolled' );
      $node->set_value( $row['total'] );
    }

    // hold types
    /////////////////////////////////////////////////////////////////////////////////////////////
    $cat_sel = clone $select;
    $cat_sel->add_table_column( 'hold_type', 'name' );
    $cat_sel->add_table_column( 'hold_type', 'type' );

    $cat_mod = clone $modifier;
    $cat_mod->where( 'queue.name', 'LIKE', '% hold' );
    $cat_mod->join(
      'participant_last_hold', 'queue_has_participant.participant_id', 'participant_last_hold.participant_id' );
    $cat_mod->join( 'hold', 'participant_last_hold.hold_id', 'hold.id' );
    $cat_mod->join( 'hold_type', 'hold.hold_type_id', 'hold_type.id' );
    $cat_mod->group( 'hold_type.id' );

    foreach( $db->get_all( sprintf( '%s %s', $cat_sel->get_sql(), $cat_mod->get_sql() ) ) as $row )
    {
      $parent_node = $site_node_lookup[$row['site']]->find_node( 'Hold Types' );
      $node = $parent_node->find_node( $row['type'].': '.$row['name'] );
      $node->set_value( $row['total'] );
    }

    // trace types
    /////////////////////////////////////////////////////////////////////////////////////////////
    $cat_sel = clone $select;
    $cat_sel->add_table_column( 'trace_type', 'name' );

    $cat_mod = clone $modifier;
    $cat_mod->where( 'queue.name', '=', 'tracing' );
    $cat_mod->join(
      'participant_last_trace', 'queue_has_participant.participant_id', 'participant_last_trace.participant_id' );
    $cat_mod->join( 'trace', 'participant_last_trace.trace_id', 'trace.id' );
    $cat_mod->join( 'trace_type', 'trace.trace_type_id', 'trace_type.id' );
    $cat_mod->group( 'trace_type.id' );

    foreach( $db->get_all( sprintf( '%s %s', $cat_sel->get_sql(), $cat_mod->get_sql() ) ) as $row )
    {
      $parent_node = $site_node_lookup[$row['site']]->find_node( 'Trace Types' );
      $node = $parent_node->find_node( $row['name'] );
      $node->set_value( $row['total'] );
    }

    // proxy types
    /////////////////////////////////////////////////////////////////////////////////////////////
    $cat_sel = clone $select;
    $cat_sel->add_table_column( 'proxy_type', 'name' );

    $cat_mod = clone $modifier;
    $cat_mod->where( 'queue.name', '=', 'proxy' );
    $cat_mod->join(
      'participant_last_proxy', 'queue_has_participant.participant_id', 'participant_last_proxy.participant_id' );
    $cat_mod->join( 'proxy', 'participant_last_proxy.proxy_id', 'proxy.id' );
    $cat_mod->join( 'proxy_type', 'proxy.proxy_type_id', 'proxy_type.id' );
    $cat_mod->group( 'proxy_type.id' );

    foreach( $db->get_all( sprintf( '%s %s', $cat_sel->get_sql(), $cat_mod->get_sql() ) ) as $row )
    {
      $parent_node = $site_node_lookup[$row['site']]->find_node( 'Proxy Types' );
      $node = $parent_node->find_node( $row['name'] );
      $node->set_value( $row['total'] );
    }

    // not yet called
    /////////////////////////////////////////////////////////////////////////////////////////////
    $cat_sel = clone $select;
    $cat_sel->add_table_column( 'script', 'name', 'qnaire' );

    $cat_mod = clone $modifier;
    $cat_mod->where( 'queue.name', '=', 'new participant' );
    $cat_mod->group( 'script.name' );

    foreach( $db->get_all( sprintf( '%s %s', $cat_sel->get_sql(), $cat_mod->get_sql() ) ) as $row )
    {
      $parent_node = $site_node_lookup[$row['site']]->find_node( ucWords( $row['qnaire'] ).' Interview' );
      $node = $parent_node->find_node( 'Not yet called' );
      $node->set_value( $row['total'] );
    }

    // call in progress
    /////////////////////////////////////////////////////////////////////////////////////////////
    $cat_sel = clone $select;
    $cat_sel->add_table_column( 'script', 'name', 'qnaire' );

    $cat_mod = clone $modifier;
    $cat_mod->where( 'queue.name', '=', 'assigned' );
    $cat_mod->group( 'script.name' );

    foreach( $db->get_all( sprintf( '%s %s', $cat_sel->get_sql(), $cat_mod->get_sql() ) ) as $row )
    {
      $parent_node = $site_node_lookup[$row['site']]->find_node( ucWords( $row['qnaire'] ).' Interview' );
      $node = $parent_node->find_node( 'Call in Progress' );
      $node->set_value( $row['total'] );
    }

    // call statuses
    /////////////////////////////////////////////////////////////////////////////////////////////
    foreach( $call_status_list as $call_status )
    {
      $cat_sel = clone $select;
      $cat_sel->add_table_column( 'script', 'name', 'qnaire' );

      $cat_mod = clone $modifier;
      $cat_mod->where( 'queue.name', '=', $call_status );
      $cat_mod->group( 'script.name' );

      foreach( $db->get_all( sprintf( '%s %s', $cat_sel->get_sql(), $cat_mod->get_sql() ) ) as $row )
      {
        $parent_node = $site_node_lookup[$row['site']]->find_node( ucWords( $row['qnaire'] ).' Interview' );
        $node = $parent_node->find_node( ucWords( $call_status ) );
        $node->set_value( $row['total'] );
      }
    }

    // phone version completed
    /////////////////////////////////////////////////////////////////////////////////////////////
    $cat_sel = clone $select;
    $cat_sel->add_table_column( 'script', 'name', 'qnaire' );
    $cat_sel->remove_column_by_alias( 'site' );
    $cat_sel->add_column( 'IFNULL( interview_site.name, site.name )', 'site', false );

    $cat_mod = clone $modifier;
    $cat_mod->where( 'queue.name', '=', 'all' );
    $cat_mod->join( 'interview', 'queue_has_participant.participant_id', 'interview.participant_id' );
    $cat_mod->left_join( 'site', 'interview.site_id', 'interview_site.id', 'interview_site' );
    $cat_mod->remove_join( 'qnaire' );
    $cat_mod->remove_join( 'script' );
    $cat_mod->join( 'qnaire', 'interview.qnaire_id', 'qnaire.id' );
    $cat_mod->join( 'script', 'qnaire.script_id', 'script.id' );
    $cat_mod->where( 'interview.end_datetime', '!=', NULL );
    $cat_mod->where( 'interview.method', '=', 'phone' );
    $cat_mod->group( 'script.name' );
    $cat_mod->replace_where( 'site.id', 'IFNULL( interview_site.id, site.id )' );
    $cat_mod->replace_group( 'queue_has_participant.site_id', 'IFNULL( interview_site.id, site.id )' );
    $cat_mod->where( 'IFNULL( interview_site.name, site.name )', '!=', NULL );

    foreach( $db->get_all( sprintf( '%s %s', $cat_sel->get_sql(), $cat_mod->get_sql() ) ) as $row )
    {
      $parent_node = $site_node_lookup[$row['site']]->find_node( ucWords( $row['qnaire'] ).' Interview' );
      $node = $parent_node->find_node( 'Phone Version Completed' );
      $node->set_value( $row['total'] );
    }

    // web version in progress
    /////////////////////////////////////////////////////////////////////////////////////////////
    $cat_sel = clone $select;
    $cat_sel->add_table_column( 'script', 'name', 'qnaire' );

    $cat_mod = clone $modifier;
    $cat_mod->where( 'queue.name', '=', 'web version' );
    $cat_mod->group( 'script.name' );

    foreach( $db->get_all( sprintf( '%s %s', $cat_sel->get_sql(), $cat_mod->get_sql() ) ) as $row )
    {
      $parent_node = $site_node_lookup[$row['site']]->find_node( ucWords( $row['qnaire'] ).' Interview' );
      $node = $parent_node->find_node( 'Web Version in Progress' );
      $node->set_value( $row['total'] );
    }

    // web version completed
    /////////////////////////////////////////////////////////////////////////////////////////////
    $cat_sel = clone $select;
    $cat_sel->add_table_column( 'script', 'name', 'qnaire' );
    $cat_sel->remove_column_by_alias( 'site' );
    $cat_sel->add_column( 'IFNULL( interview_site.name, site.name )', 'site', false );

    $cat_mod = clone $modifier;
    $cat_mod->where( 'queue.name', '=', 'all' );
    $cat_mod->join( 'interview', 'queue_has_participant.participant_id', 'interview.participant_id' );
    $cat_mod->left_join( 'site', 'interview.site_id', 'interview_site.id', 'interview_site' );
    $cat_mod->remove_join( 'qnaire' );
    $cat_mod->remove_join( 'script' );
    $cat_mod->join( 'qnaire', 'interview.qnaire_id', 'qnaire.id' );
    $cat_mod->join( 'script', 'qnaire.script_id', 'script.id' );
    $cat_mod->where( 'interview.end_datetime', '!=', NULL );
    $cat_mod->where( 'interview.method', '=', 'web' );
    $cat_mod->group( 'script.name' );
    $cat_mod->replace_where( 'site.id', 'IFNULL( interview_site.id, site.id )' );
    $cat_mod->replace_group( 'queue_has_participant.site_id', 'IFNULL( interview_site.id, site.id )' );
    $cat_mod->where( 'IFNULL( interview_site.name, site.name )', '!=', NULL );

    foreach( $db->get_all( sprintf( '%s %s', $cat_sel->get_sql(), $cat_mod->get_sql() ) ) as $row )
    {
      $parent_node = $site_node_lookup[$row['site']]->find_node( ucWords( $row['qnaire'] ).' Interview' );
      $node = $parent_node->find_node( 'Web Version Completed' );
      $node->set_value( $row['total'] );
    }

    // completed
    /////////////////////////////////////////////////////////////////////////////////////////////
    $cat_sel = clone $select;
    $cat_sel->add_table_column( 'script', 'name', 'qnaire' );
    $cat_sel->remove_column_by_alias( 'site' );
    $cat_sel->add_column( 'IFNULL( interview_site.name, site.name )', 'site', false );

    $cat_mod = clone $modifier;
    $cat_mod->where( 'queue.name', '=', 'all' );
    $cat_mod->join( 'interview', 'queue_has_participant.participant_id', 'interview.participant_id' );
    $cat_mod->left_join( 'site', 'interview.site_id', 'interview_site.id', 'interview_site' );
    $cat_mod->remove_join( 'qnaire' );
    $cat_mod->remove_join( 'script' );
    $cat_mod->join( 'qnaire', 'interview.qnaire_id', 'qnaire.id' );
    $cat_mod->join( 'script', 'qnaire.script_id', 'script.id' );
    $cat_mod->where( 'interview.end_datetime', '!=', NULL );
    $cat_mod->group( 'script.name' );
    $cat_mod->replace_where( 'site.id', 'IFNULL( interview_site.id, site.id )' );
    $cat_mod->replace_group( 'queue_has_participant.site_id', 'IFNULL( interview_site.id, site.id )' );
    $cat_mod->where( 'IFNULL( interview_site.name, site.name )', '!=', NULL );

    foreach( $db->get_all( sprintf( '%s %s', $cat_sel->get_sql(), $cat_mod->get_sql() ) ) as $row )
    {
      $parent_node = $site_node_lookup[$row['site']]->find_node( ucWords( $row['qnaire'] ).' Interview' );
      $node = $parent_node->find_node( 'Completed Interviews' );
      $node->set_value( $row['total'] );
    }

    // create summary node and finish
    /////////////////////////////////////////////////////////////////////////////////////////////
    $first_node = NULL;
    if( $db_role->all_sites )
    {
      // create a summary node of all sites
      $first_node = $this->root_node->get_summary_node();
      if( !is_null( $first_node ) )
      {
        $first_node->set_label( 'Summary of All Sites' );
        $this->root_node->add_child( $first_node, true );
      }
    }
    else
    {
      $first_node = $this->root_node->find_node( $db_site->name );
    }

    if( !is_null( $first_node ) )
    {
      // go through the first node and remove all hold types with a value of 0
      $hold_type_node = $first_node->find_node( 'Hold Types' );
      $removed_label_list = $hold_type_node->remove_empty_children();

      // and remove them from other nodes as well
      $this->root_node->each( function( $node ) use( $removed_label_list ) {
        $hold_type_node = $node->find_node( 'Hold Types' );
        $hold_type_node->remove_child_by_label( $removed_label_list );
      } );

      // go through the first node and remove all trace types with a value of 0
      $trace_type_node = $first_node->find_node( 'Trace Types' );
      $removed_label_list = $trace_type_node->remove_empty_children();

      // and remove them from other nodes as well
      $this->root_node->each( function( $node ) use( $removed_label_list ) {
        $trace_type_node = $node->find_node( 'Trace Types' );
        $trace_type_node->remove_child_by_label( $removed_label_list );
      } );

      // go through the first node and remove all proxy types with a value of 0
      $proxy_type_node = $first_node->find_node( 'Proxy Types' );
      $removed_label_list = $proxy_type_node->remove_empty_children();

      // and remove them from other nodes as well
      $this->root_node->each( function( $node ) use( $removed_label_list ) {
        $proxy_type_node = $node->find_node( 'Proxy Types' );
        $proxy_type_node->remove_child_by_label( $removed_label_list );
      } );
    }
  }
}
