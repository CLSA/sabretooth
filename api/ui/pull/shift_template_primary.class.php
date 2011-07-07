<?php
/**
 * shift_template_primary.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui\pull;
use sabretooth\log, sabretooth\util;

/**
 * pull: shift_template primary
 * 
 * @package sabretooth\ui
 */
class shift_template_primary extends base_primary
{
  /**
   * Constructor
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args Pull arguments.
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'shift_template', $args );
  }
}
?>
