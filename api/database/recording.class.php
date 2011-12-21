<?php
/**
 * recording.class.php
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
 * recording: record
 *
 * @package sabretooth\database
 */
class recording extends record
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
