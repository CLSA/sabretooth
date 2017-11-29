<?php
/**
 * patch.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */

namespace sabretooth\service\participant;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * Special service for handling the patch meta-resource
 */
class patch extends \cenozo\service\participant\patch
{
  /**
   * Override parent method
   */
  protected function execute()
  {
    parent::execute();

    $db_participant = $this->get_leaf_record();

    // update the participant's queue, if requested
    if( $this->get_argument( 'repopulate', false ) ) $db_participant->repopulate_queue();
  }
}
