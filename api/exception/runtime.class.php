<?php
/**
 * runtime.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\exception
 * @filesource
 */

namespace sabretooth\exception;

/**
 * runtime: runtime exceptions
 * 
 * All generic exceptions which only occur at runtime use this class to throw exceptions.
 * @package sabretooth\exception
 */
class runtime extends base_exception
{
  /**
   * Constructor
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $message the error message.
   * @param exception $previous the previous exception used for the exception chaining
   * @access public
   */
  public function __construct( $message, $previous = NULL )
  {
    parent::__construct( "runtime error: \"$message\"", $previous );
  }
}
?>
