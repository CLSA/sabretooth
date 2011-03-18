<?php
/**
 * queue.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\database
 * @filesource
 */

namespace sabretooth\database;

/**
 * queue: record
 *
 * @package sabretooth\database
 */
class queue extends record
{
  /**
   * Returns the number of participants currently in the queue.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param modifier $modifier Modifications to the queue.
   * @return int
   * @access public
   */
  public function get_participant_count( $modifier = NULL )
  {
    if( is_null( $modifier ) ) $modifier = new modifier();

    // get the name of the queue-view
    return static::db()->get_one(
      sprintf( "SELECT COUNT(*) FROM %s %s",
               $this->view,
               $modifier->get_sql() ) );
  }

  /**
   * Returns a list of participants currently in the queue.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param modifier $modifier Modifications to the queue.
   * @return array( participant )
   * @access public
   */
  public function get_participant_list( $modifier = NULL )
  {
    if( is_null( $modifier ) ) $modifier = new modifier();

    // get the name of the queue-view
    $participant_ids = static::db()->get_col(
      sprintf( "SELECT id FROM %s %s",
               $this->view,
               $modifier->get_sql() ) );

    $participants = array();
    foreach( $participant_ids as $id ) $participants[] = new participant( $id );
    return $participants;
  }
}
?>
