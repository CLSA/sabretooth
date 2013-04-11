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
  public function get_filename()
  {
    // make sure the recording has the interview set
    if( is_null( $this->interview_id ) )
    {
      log::warning(
        'Tried to get filename of recording without both interview_id.' );
      return NULL;
    }
    
    $padded_interview_id = str_pad( $this->interview_id, 7, '0', STR_PAD_LEFT );
    $padded_rank = str_pad( is_null( $this->rank ) ? 1 : $this->rank, 2, '0', STR_PAD_LEFT );
    $filename = sprintf( '%s/%s/%s_%s-%s',
                         substr( $padded_interview_id, 0, 3 ),
                         substr( $padded_interview_id, 3, 2 ),
                         substr( $padded_interview_id, 5 ),
                         is_null( $this->assignment_id ) ? 0 : $this->assignment_id,
                         $padded_rank );
    
    return $filename;
  }
}
?>
