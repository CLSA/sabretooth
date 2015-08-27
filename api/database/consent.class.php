<?php
/**
 * consent.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\database;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * consent: record
 */
class consent extends \cenozo\database\consent
{
  /**
   * Overrides the parent save method.
   */
  public function save()
  {
    // if we changed certain columns then update the queue
    $update_queue = $this->has_column_changed( 'accept' );
    parent::save();
    if( $update_queue ) $this->get_participant()->update_queue_status();
  }

  /**
   * Override the parent method
   */
  public function delete()
  {
    $db_participant = $this->get_participant();
    parent::delete();
    $db_participant->update_queue_status();
  }
}
