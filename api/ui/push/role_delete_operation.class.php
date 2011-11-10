<?php
/**
 * role_delete_operation.class.php
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
 * push: role delete_operation
 * 
 * @package sabretooth\ui
 */
class role_delete_operation extends base_delete_record
{
  /**
   * Constructor.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args Push arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'role', 'operation', $args );
  }

  /**
   * Overrides the parent method since no operation_delete method exists.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function finish()
  {
    $this->get_record()->remove_operation( $this->get_argument( 'remove_id' ) );
  }
}
?>
