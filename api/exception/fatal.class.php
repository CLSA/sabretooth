<?php
/**
 * fatal.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\exception
 * @filesource
 */

namespace sabretooth\exception;

/**
 * fatal: fatal exceptions
 * 
 * Describes an unrecoverable fatal error.
 * @package sabretooth\exception
 */
class fatal extends base_exception
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
    $this->raw_message = $message;
    parent::__construct( 'fatal error: "'.$message.'"', $context, $previous );
  }
}
?>
