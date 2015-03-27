<?php
/**
 * query.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\service\queue;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * The base class of all query (collection-based get) services
 */
class query extends \cenozo\service\query
{
  /**
   * Processes arguments, preparing them for the service.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access protected
   */
  protected function prepare()
  {
    parent::prepare();

    // add the total number of participants
    if( $this->select->has_table_column( '', 'participant_count' ) )
    {
      $this->modifier->left_join( 'queue_has_participant', 'queue.id', 'queue_has_participant.queue_id' );
      $this->modifier->group( 'queue.id' );
      $this->select->add_column(
        'IF( queue_has_participant.queue_id IS NULL, 0, COUNT(*) )', 'participant_count', false );
    }

    // only return ranked queues
    $this->modifier->where( 'rank', '!=', NULL );
  }
}
