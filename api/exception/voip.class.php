<?php
/**
 * voip.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\exception;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * voip: voip exceptions
 * 
 * All exceptions caused because of the voip connection.
 */
class voip extends \cenozo\exception\runtime
{
  /**
   * Constructor
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $message A message describing the exception.
   * @param string|int $context The exceptions context, either a function name or error code.
   * @param exception $previous The previous exception used for the exception chaining.
   * @access public
   */
  public function __construct( $message, $context, $previous = NULL )
  {
    parent::__construct( $message, $context, $previous );
  }
}
?>
