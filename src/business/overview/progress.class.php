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
  protected function build()
  {
    $phone_call_class_name = lib::get_class_name( 'database\phone_call' );
    $qnaire_class_name = lib::get_class_name( 'database\qnaire' );
    $state_class_name = lib::get_class_name( 'database\state' );

    $session = lib::create( 'business\session' );
    $db = $session->get_database();
    $db_application = $session->get_application();
    $db_site = $session->get_site();
    $db_role = $session->get_role();
    $db_user = $session->get_user();

    $data = array();

    // get a list of all states
    $state_sel = lib::create( 'database\select' );
    $state_sel->add_column( 'name' );
    $state_mod = lib::create( 'database\modifier' );
    $state_mod->order( 'name' );
    $state_list = array();
    foreach( $state_class_name::select( $state_sel, $state_mod ) as $state ) $state_list[] = $state['name'];

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

    $modifier = lib::create( 'database\modifier' );
    $modifier->join( 'queue', 'queue_has_participant.queue_id', 'queue.id' );
    $modifier->left_join( 'site', 'queue_has_participant.site_id', 'site.id' );
    $modifier->left_join( 'qnaire', 'queue_has_participant.qnaire_id', 'qnaire.id' );
    $modifier->left_join( 'script', 'qnaire.script_id', 'script.id' );
    if( !$db_role->all_sites ) $modifier->where( 'site.id', '=', $db_site->id );
    $modifier->group( 'queue_has_participant.site_id' );

    // start with the participant totals
    /////////////////////////////////////////////////////////////////////////////////////////////
    $all_mod = clone $modifier;
    $all_mod->where( 'queue.name', '=', 'all' );

    foreach( $db->get_all( sprintf( '%s %s', $select->get_sql(), $all_mod->get_sql() ) ) as $row )
    {
      $node = $this->add_root_item( $row['site'] );
      $this->add_item( $node, 'All Participants', $row['total'] );
      $this->add_item( $node, 'Inactive', 0 );
      $this->add_item( $node, 'Refused Consent', 0 );
      $state_node = $this->add_item( $node, 'Conditions' );
      foreach( $state_list as $state ) $this->add_item( $state_node, $state, 0 );
      foreach( $qnaire_list as $qnaire )
      {
        $qnaire_node = $this->add_item( $node, $qnaire.' Interview' );
        $this->add_item( $qnaire_node, 'Not yet called', 0 );
        $this->add_item( $qnaire_node, 'Call in progress', 0 );
        foreach( $call_status_list as $call_status ) $this->add_item( $qnaire_node, ucWords( $call_status ), 0 );
        $this->add_item( $qnaire_node, 'Completed Interviews', 0 );
      }
      $site_node_lookup[$row['site']] = $node;
    }

    // inactive participants
    /////////////////////////////////////////////////////////////////////////////////////////////
    $inactive_mod = clone $modifier;
    $inactive_mod->where( 'queue.name', '=', 'inactive' );

    foreach( $db->get_all( sprintf( '%s %s', $select->get_sql(), $inactive_mod->get_sql() ) ) as $row )
    {
      $node = $site_node_lookup[$row['site']]->find_node( 'Inactive' );
      $node->set_value( $row['total'] );
    }

    // withdrawn participants
    /////////////////////////////////////////////////////////////////////////////////////////////
    $refused_mod = clone $modifier;
    $refused_mod->where( 'queue.name', '=', 'refused consent' );

    foreach( $db->get_all( sprintf( '%s %s', $select->get_sql(), $refused_mod->get_sql() ) ) as $row )
    {
      $node = $site_node_lookup[$row['site']]->find_node( 'Refused Consent' );
      $node->set_value( $row['total'] );
    }

    // states
    /////////////////////////////////////////////////////////////////////////////////////////////
    $state_sel = clone $select;
    $state_sel->add_table_column( 'script', 'name', 'qnaire' );
    $state_sel->add_table_column( 'state', 'name', 'state' );

    $state_mod = clone $modifier;
    $state_mod->where( 'queue.name', '=', 'condition' );
    $state_mod->join( 'participant', 'queue_has_participant.participant_id', 'participant.id' );
    $state_mod->join( 'state', 'participant.state_id', 'state.id' );
    $state_mod->group( 'participant.state_id' );

    foreach( $db->get_all( sprintf( '%s %s', $state_sel->get_sql(), $state_mod->get_sql() ) ) as $row )
    {
      $parent_node = $site_node_lookup[$row['site']]->find_node( 'Conditions' );
      $node = $parent_node->find_node( $row['state'] );
      $node->set_value( $row['total'] );
    }

    // not yet called
    /////////////////////////////////////////////////////////////////////////////////////////////
    $new_sel = clone $select;
    $new_sel->add_table_column( 'script', 'name', 'qnaire' );

    $new_mod = clone $modifier;
    $new_mod->where( 'queue.name', '=', 'new participant' );
    $new_mod->group( 'script.name' );

    foreach( $db->get_all( sprintf( '%s %s', $new_sel->get_sql(), $new_mod->get_sql() ) ) as $row )
    {
      $parent_node = $site_node_lookup[$row['site']]->find_node( ucWords( $row['qnaire'] ).' Interview' );
      $node = $parent_node->find_node( 'Not yet called' );
      $node->set_value( $row['total'] );
    }

    // call in progress
    /////////////////////////////////////////////////////////////////////////////////////////////
    $new_sel = clone $select;
    $new_sel->add_table_column( 'script', 'name', 'qnaire' );

    $new_mod = clone $modifier;
    $new_mod->where( 'queue.name', '=', 'assigned' );
    $new_mod->group( 'script.name' );

    foreach( $db->get_all( sprintf( '%s %s', $new_sel->get_sql(), $new_mod->get_sql() ) ) as $row )
    {
      $parent_node = $site_node_lookup[$row['site']]->find_node( ucWords( $row['qnaire'] ).' Interview' );
      $node = $parent_node->find_node( 'Call in progress' );
      $node->set_value( $row['total'] );
    }

    // call statuses
    /////////////////////////////////////////////////////////////////////////////////////////////
    foreach( $call_status_list as $call_status )
    {
      $new_sel = clone $select;
      $new_sel->add_table_column( 'script', 'name', 'qnaire' );

      $new_mod = clone $modifier;
      $new_mod->where( 'queue.name', '=', $call_status );
      $new_mod->group( 'script.name' );

      foreach( $db->get_all( sprintf( '%s %s', $new_sel->get_sql(), $new_mod->get_sql() ) ) as $row )
      {
        $parent_node = $site_node_lookup[$row['site']]->find_node( ucWords( $row['qnaire'] ).' Interview' );
        $node = $parent_node->find_node( ucWords( $call_status ) );
        $node->set_value( $row['total'] );
      }
    }

    // completed
    /////////////////////////////////////////////////////////////////////////////////////////////
    $completed_sel = clone $select;
    $completed_sel->add_table_column( 'script', 'name', 'qnaire' );

    $completed_mod = clone $modifier;
    $completed_mod->where( 'queue.name', '=', 'all' );
    $completed_mod->join( 'interview', 'queue_has_participant.participant_id', 'interview.participant_id' );
    $completed_mod->remove_join( 'qnaire' );
    $completed_mod->remove_join( 'script' );
    $completed_mod->join( 'qnaire', 'interview.qnaire_id', 'qnaire.id' );
    $completed_mod->join( 'script', 'qnaire.script_id', 'script.id' );
    $completed_mod->where( 'interview.end_datetime', '!=', NULL );
    $completed_mod->group( 'script.name' );

    foreach( $db->get_all( sprintf( '%s %s', $completed_sel->get_sql(), $completed_mod->get_sql() ) ) as $row )
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
      // go through the first node and remove all states with a value of 0
      $state_node = $first_node->find_node( 'Conditions' );
      $removed_label_list = $state_node->remove_empty_children();

      // and remove them from other nodes as well
      $this->root_node->each( function( $node ) use( $removed_label_list ) {
        $state_node = $node->find_node( 'Conditions' );
        $state_node->remove_child_by_label( $removed_label_list );
      } );
    }
  }
}
