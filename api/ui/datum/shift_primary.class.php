<?php
/**
 * shift_primary.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui\datum;
use sabretooth\log, sabretooth\util;

/**
 * datum shift primary
 * 
 * @package sabretooth\ui
 */
class shift_primary extends base_primary
{
  /**
   * Constructor
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args An associative array of arguments to be processed by the datum
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'shift', $args );
  }
}
?>
