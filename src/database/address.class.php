<?php
/**
 * address.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\database;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * address: record
 */
class address extends \cenozo\database\address
{
  /**
   * Override the parent method
   */
  public function save()
  {
    // if we changed certain columns then update the queue
    $update_queue = !is_null( $this->participant_id ) &&
      $this->has_column_changed( array(
        'active', 'rank', 'international', 'region_id', 'postcode', 'timezone_offset', 'daylight_savings',
        'january', 'february', 'march', 'april', 'may', 'june',
        'july', 'august', 'september', 'october', 'november', 'december' ) );
    parent::save();
    if( $update_queue ) $this->get_participant()->repopulate_queue( true );
  }

  /**
   * Override the parent method
   */
  public function delete()
  {
    $db_participant = $this->get_participant();
    parent::delete();
    $db_participant->repopulate_queue( true );
  }
}
