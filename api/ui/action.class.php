<?php
/**
 * action.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui;

/**
 * action: The base class of all actions
 * 
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
  public function __construct( $subject, $name )
  {
    parent::__construct( 'action', $subject, $name );
  }

  /**
   * Perform the action.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @abstract
   * @access public
   */
  abstract public function execute();
}
?>
