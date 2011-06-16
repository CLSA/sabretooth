<?php
/**
 * shift_template_edit.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui\action;
use sabretooth\log, sabretooth\util;
use sabretooth\business as bus;
use sabretooth\database as db;
use sabretooth\exception as exc;

/**
 * action shift template edit
 *
 * Edit a shift template.
 * @package sabretooth\ui
 */
class shift_template_edit extends base_edit
{
  /**
   * Constructor.
   * @autho Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args Action arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'shift_template', $args );
  }
}
?>
