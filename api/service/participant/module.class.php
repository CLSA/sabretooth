<?php
/**
 * module.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\service\participant;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * Performs operations which effect how this module is used in a service
 */
class module extends \cenozo\service\participant\module
{
  /**
   * Extend parent method
   */
  public function prepare_read( $select, $modifier )
  {
    parent::prepare_read( $select, $modifier );

    if( $select->has_table_columns( 'queue' ) || $select->has_table_columns( 'qnaire' ) )
    {
      $modifier->join( 'queue_has_participant', 'participant.id', 'queue_has_participant.participant_id' );
      $modifier->join( 'queue', 'queue_has_participant.queue_id', 'queue.id' );
      $modifier->join( 'qnaire', 'queue_has_participant.qnaire_id', 'qnaire.id' );
      $modifier->where( 'participant.active', '=', true );
      $modifier->where( 'queue.rank', '!=', NULL );
      
      $join_mod = lib::create( 'database\modifier' );
      $join_mod->where( 'queue_has_participant.queue_id', '=', 'queue_state.queue_id', false );
      $join_mod->where( 'queue_has_participant.site_id', '=', 'queue_state.site_id', false );
      $join_mod->where( 'queue_has_participant.qnaire_id', '=', 'queue_state.qnaire_id', false );
      $modifier->join_modifier( 'queue_state', $join_mod, 'left' );
      $modifier->where( 'queue_state.id', '=', NULL );

      // TODO: only show reserved appointments to appointment.user_id

      // must force all queues to repopulate
      $queue_class_name = lib::get_class_name( 'database\queue' );
      $queue_class_name::repopulate_time_specific();
    }
  }
}
