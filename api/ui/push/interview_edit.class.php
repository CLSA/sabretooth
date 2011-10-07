<?php
/**
 * interview_edit.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui\push;
use sabretooth\log, sabretooth\util;
use sabretooth\business as bus;
use sabretooth\database as db;
use sabretooth\exception as exc;

/**
 * push: interview edit
 *
 * Edit a interview.
 * @package sabretooth\ui
 */
class interview_edit extends base_edit
{
  /**
   * Constructor.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args Push arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'interview', $args );
  }
  
  /**
   * Interviews cannot be edited directly, instead, this method allows interviews to
   * be force-completed.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @throws exception\permission
   * @access public
   */
  public function finish()
  {
    $columns = $this->get_argument( 'columns', array() );

    if( array_key_exists( 'completed', $columns ) )
    {
      // force complete the interview or throw a notice (we cannot un-complete)
      if( 1 == $columns['completed'] )
      {
        $this->get_record()->force_complete();
      }
      else throw new exc\notice( 'Interviews cannot be un-completed.', __METHOD__ );
    }
    else throw new exc\notice(
      'Only the "completed" state of an interview may be edited.', __METHOD__ );
  }
}
?>
