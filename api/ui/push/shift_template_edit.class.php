<?php
/**
 * shift_template_edit.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui\push;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * push: shift template edit
 *
 * Edit a shift template.
 */
class shift_template_edit extends \cenozo\ui\push\base_edit
{
  /**
   * Constructor.
   * @autho Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args Push arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'shift_template', $args );
  }
}
?>
