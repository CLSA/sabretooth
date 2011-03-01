<?php
/**
 * sample_delete_qnaire.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui;

/**
 * action sample delete_qnaire
 * 
 * @package sabretooth\ui
 */
class sample_delete_qnaire extends base_delete_record
{
  /**
   * Constructor.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args Action arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'sample', 'qnaire', $args );
  }
}
?>
