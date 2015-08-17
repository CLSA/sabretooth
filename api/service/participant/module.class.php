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

      // must force all queues to repopulate
      $queue_class_name = lib::get_class_name( 'database\queue' );
      $queue_mod = lib::create( 'database\modifier' );
      $queue_mod->where( 'rank', '!=', NULL );
      foreach( $queue_class_name::select_objects( $queue_mod ) as $db_queue )
        $db_queue->populate_time_specific();
    }
  }
}
