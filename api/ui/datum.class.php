<?php
/**
 * datum.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui;

/**
 * The base class of all datum.
 * 
 * @package sabretooth\ui
 */
abstract class datum extends operation
{
  /**
   * Constructor
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $subject The subject of the operation.
   * @param string $name The name of the operation.
   * @param array $args An associative array of arguments to be processed by the datum.
   * @access public
   */
  public function __construct( $subject, $name, $args )
  {
    parent::__construct( 'datum', $subject, $name, $args );
  }

  /**
   * Returns the data provided by this datum operation.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @abstract
   * @access public
   */
  abstract public function get_data();
}
?>
