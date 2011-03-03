<?php
/**
 * qnaire_new_phase.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui;

/**
 * action qnaire new_phase
 * 
 * @package sabretooth\ui
 */
class qnaire_new_phase extends base_new_record
{
  /**
   * Constructor.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args Action arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'qnaire', 'phase', $args );
  }
}
?>
