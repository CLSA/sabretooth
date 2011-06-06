<?php
/**
 * phase.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\database
 * @filesource
 */

namespace sabretooth\database;
use sabretooth\log, sabretooth\util;
use sabretooth\business as bus;
use sabretooth\exception as exc;

/**
 * phase: record
 *
 * @package sabretooth\database
 */
class phase extends has_rank
{
  /**
   * Returns this phase's survey.
   * This overrides the parent's magic method because the survey record is outside the main db.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return surveys
   * @access public
   */
  public function get_survey()
  {
    // check the primary key value
    $primary_key_name = static::get_primary_key_name();
    if( is_null( $this->$primary_key_name ) )
    {
      log::warning( 'Tried to delete record with no id.' );
      return;
    }
    
    return new limesurvey\surveys( $this->sid );
  }

  /**
   * The type of record which the record has a rank for.
   * @var string
   * @access protected
   * @static
   */
  protected static $rank_parent = 'qnaire';
}
?>
