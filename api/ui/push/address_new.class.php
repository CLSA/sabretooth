<?php
/**
 * address_new.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui\push;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * push: address new
 *
 * Create a new address.
 */
class address_new extends \cenozo\ui\push\address_new
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

    // if the owner is a participant then update their queue status
    $db_participant = $this->get_record()->get_person()->get_participant();
    if( !is_null( $db_participant ) ) $db_participant->update_queue_status();
  }
}
