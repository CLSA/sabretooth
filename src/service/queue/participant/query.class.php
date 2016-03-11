<?php
/**
 * query.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\service\queue\participant;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * Special queue for handling the query meta-resource
 */
class query extends \cenozo\service\query
{
  /**
   * Override parent method
   */
  protected function prepare()
  {
    parent::prepare();

    $this->modifier->join( 'queue_has_participant', 'participant.id', 'queue_has_participant.participant_id' );
    $this->modifier->join( 'queue', 'queue_has_participant.queue_id', 'queue.id' );
    $this->modifier->left_join( 'qnaire', 'queue_has_participant.qnaire_id', 'qnaire.id' );
    $this->modifier->left_join( 'script', 'qnaire.script_id', 'script.id' );
  }
}
