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
class module extends \cenozo\service\site_restricted_module
{
  /**
   * Extend parent method
   */
  public function prepare_read( $select, $modifier )
  {
    // if the "full" parameter isn't included then only show ranked queues
    $full = $this->get_argument( 'full', false );
    if( !$full ) $modifier->where( 'queue.rank', '!=', NULL );

    // add the total number of participants
    if( $select->has_column( 'participant_count' ) )
    {
      $join_sel = lib::create( 'database\select' );
      $join_sel->from( 'queue' );
      $join_sel->add_column( 'id', 'queue_id' );
      $join_sel->add_column(
        'MAX( queue_has_participant.update_timestamp )', 'last_repopulation', false, 'timestamp' );
      $join_sel->add_column(
        'IF( queue_has_participant.participant_id IS NOT NULL, COUNT(*), 0 )',
        'participant_count', false );

      $join_mod = lib::create( 'database\modifier' );
      $join_mod->left_join(
        'queue_has_participant', 'queue.id', 'queue_has_participant.queue_id' );
      $join_mod->group( 'queue.id' );

      // add any where statements referring to qnaire_id, site_id, date or language_id
      if( $modifier->has_where( 'qnaire_id' ) ||
          $modifier->has_where( 'site_id' ) ||
          $modifier->has_where( 'date' ) ||
          $modifier->has_where( 'language_id' ) )
      {
        // move these statements from the main modifier to the sub-select
        $modifier->remove_where( 'qnaire_id' );
        $modifier->remove_where( 'site_id' );
        $modifier->remove_where( 'date' );
        $modifier->remove_where( 'language_id' );

        $mod_arg = util::json_decode( $this->get_argument( 'modifier' ) );

        foreach( $mod_arg->where as $where )
        {
          // the column either refers to the queue_has_participant or the participant table
          $table = 'language_id' == $where->column
                 ? 'participant'
                 : 'queue_has_participant';

          if( 'participant' == $table )
            $join_mod->left_join( 'participant', 'queue_has_participant.participant_id', 'participant.id' );

          $column = sprintf( '%s.%s', $table, $where->column );
          $join_mod->where( $column, $where->operator, $where->value );
        }
      }

      // restrict by site
      $db_restrict_site = $this->get_restricted_site();
      if( !is_null( $db_restrict_site ) )
        $join_mod->where( 'queue_has_participant.site_id', '=', $db_restrict_site->id );

      $modifier->left_join(
        sprintf( '( %s %s ) AS queue_join_participant', $join_sel->get_sql(), $join_mod->get_sql() ),
        'queue.id',
        'queue_join_participant.queue_id' );
      $select->add_column( 'last_repopulation', 'last_repopulation', false );
      $select->add_column( 'IFNULL( participant_count, 0 )', 'participant_count', false );
    }

    // if requested, repopulate queue immediately
    $repopulate = $this->get_argument( 'repopulate', false );
    if( $repopulate )
    {
      $queue_class_name = lib::get_class_name( 'database\queue' );
      if( 'full' == $repopulate )
      {
        $queue_class_name::repopulate();
        $queue_class_name::repopulate_time();
      }
      else
      {
        $interval = $queue_class_name::get_interval_since_last_repopulate_time();
        if( is_null( $interval ) || 0 < $interval->days || 0 < $interval->h || 0 < $interval->i )
          $queue_class_name::repopulate_time();
      }
    }
  }
}
