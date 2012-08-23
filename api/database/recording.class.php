<?php
/**
 * recording.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\database;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * recording: record
 */
class recording extends \cenozo\database\record
{
  /**
   * Gets the file associated with this recording
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return string
   * @access public
   */
  public function get_file()
  {
    // check the primary key value
    if( is_null( $this->id ) )
    {
      log::warning( 'Tried to query participant with no id.' );
      return NULL;
    }
    
    return sprintf( 1 == $this->rank ? 'monitor/%d_%d-out.wav' : 'monitor/%d_%d-%d-out.wav',
                    $this->interview_id,
                    is_null( $this->assignment_id ) ? 0 : $this->assignment_id,
                    $this->rank - 1 );
  }
}
?>
