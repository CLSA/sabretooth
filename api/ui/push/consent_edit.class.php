<?php
/**
 * consent_edit.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui\push;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * push: consent edit
 *
 * Create a edit consent.
 */
class consent_edit extends \cenozo\ui\push\consent_edit
{
  /**
   * This method executes the operation's purpose.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access protected
   */
  protected function execute()
  {
    parent::execute();

    // update this participant's queue status
    $this->get_record()->get_participant()->update_queue_status();
  }
}
