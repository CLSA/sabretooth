<?php
/**
 * qnaire.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\database;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * qnaire: record
 */
class qnaire extends \cenozo\database\has_rank
{
  /**
   * Returns a special event-type associated with this qnaire
   * 
   * Returns the event-type associated with the first attempt of contacting a participant for this
   * qnaire. If no event-type exists this method will return NULL.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return database\event_type
   * @access public
   */
  public function get_first_attempt_event_type()
  {
    return is_null( $this->first_attempt_event_type_id ) ?
      NULL : lib::create( 'database\event_type', $this->first_attempt_event_type_id );
  }

  /**
   * Returns a special event-type associated with this qnaire
   * 
   * Returns the event-type associated with the first time a participant is contacted for this
   * qnaire. If no event-type exists this method will return NULL.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return database\event_type
   * @access public
   */
  public function get_reached_event_type()
  {
    return is_null( $this->reached_event_type_id ) ?
      NULL : lib::create( 'database\event_type', $this->reached_event_type_id );
  }
}
