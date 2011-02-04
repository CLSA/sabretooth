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
   * @param array $args An associative array of arguments to be processed by the action
   * @access public
   */
  public function __construct( $subject, $name, $args )
  {
    parent::__construct( 'action', $subject, $name, $args );
  }

  /**
   * Perform the action.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function execute()
  {
  }
}
?>
