<?php
/**
 * phase.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\database;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * phase: record
 */
class phase extends \cenozo\database\has_rank
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
    
    return lib::create( 'database\limesurvey\surveys', $this->sid );
  }
  
  /**
   * The type of record which the record has a rank for.
   * @var string
   * @access protected
   * @static
   */
  protected static $rank_parent = 'qnaire';
}
