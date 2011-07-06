<?php
/**
 * action.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui;
use sabretooth\log, sabretooth\util;
use sabretooth\business as bus;
use sabretooth\database as db;
use sabretooth\exception as exc;

/**
 * The base class of all actions
 * 
 * @package sabretooth\ui
 */
abstract class action extends operation
{
  /**
   * Constructor
   * 
   * Defines all variables available in every action
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $subject The subject of the operation.
   * @param string $name The name of the operation.
   * @access public
   */
  public function __construct( $subject, $name, $args )
  {
    parent::__construct( 'action', $subject, $name, $args );
  }
}
?>
