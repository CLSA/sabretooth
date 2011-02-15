<?php
/**
 * contains_record.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui;

/**
 * Interface that specifies that a class is directly related to a single record.
 * @package sabretooth\ui
 */
interface contains_record
{
  /**
   * Returns this object's active record.
   * 
   * @return database\active_record
   * @access public
   */
  public function get_record();

  /**
   * Sets this object's active record.
   * 
   * @param $record database\active_record
   * @access public
   */
  public function set_record( $record );
}
