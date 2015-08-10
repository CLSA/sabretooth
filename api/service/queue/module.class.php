<?php
/**
 * module.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\service\queue;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * Performs operations which effect how this module is used in a service
 */
class module extends \cenozo\service\module
{
  /**
   * Extend parent method
   */
  public function prepare_read( $select, $modifier )
  {
    $queue_class_name = lib::get_class_name( 'database\queue' );
    $session = lib::create( 'business\session' );
    $db_role = $session->get_role();
    $db_site = $session->get_site();

    // if the "full" parameter isn't included then only show ranked queues
    $full = $this->get_argument( 'full', false );
    if( !$full ) $modifier->where( 'queue.rank', '!=', NULL );

    // add the total number of participants
    if( $select->has_column( 'participant_count' ) )
    {
      // must force all queues to repopulate
      $queue_mod = lib::create( 'database\modifier' );
      if( !$full ) $queue_mod->where( 'rank', '!=', NULL );
      foreach( $queue_class_name::select_objects( $queue_mod ) as $db_queue )
        $db_queue->populate_time_specific();

      $join_sel = lib::create( 'database\select' );
      $join_sel->from( 'queue' );
      $join_sel->add_column( 'id', 'queue_id' );
      $join_sel->add_column(
        'IF( queue_has_participant.participant_id IS NOT NULL, COUNT(*), 0 )',
        'participant_count', false );

      $join_mod = lib::create( 'database\modifier' );
      $join_mod->left_join(
        'queue_has_participant', 'queue.id', 'queue_has_participant.queue_id' );
      $join_mod->group( 'queue.id' );

      // restrict to participants in this site (for some roles)
      if( !$db_role->all_sites )
        $join_mod->where( 'queue_has_participant.site_id', '=', $db_site->id );

      $modifier->left_join(
        sprintf( '( %s %s ) AS queue_join_participant', $join_sel->get_sql(), $join_mod->get_sql() ),
        'queue.id',
        'queue_join_participant.queue_id' );
      $select->add_column( 'participant_count', 'participant_count', false );
    }
  }
}
