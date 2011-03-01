<?php
/**
 * participant_delete_sample.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui;

/**
 * action participant delete_sample
 * 
 * @package sabretooth\ui
 */
class participant_delete_sample extends base_delete_record
{
  /**
   * Constructor.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args Action arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'participant', 'sample', $args );
  }
}
?>
