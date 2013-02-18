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
    
    $padded = str_pad( $this->interview_id, 7, '0', STR_PAD_LEFT );
    $base = sprintf( 'monitor/%s/%s/%s', substr( $padded, 0, 3 ), substr( $padded, 3, 2 ), substr( $padded, 5 ) );
    return sprintf( 1 == $this->rank ? '%s_%d-out.wav' : '%s_%d-%d-out.wav',
                    $base,
                    is_null( $this->assignment_id ) ? 0 : $this->assignment_id,
                    $this->rank - 1 );
  }
}
?>
