<?php
/**
 * missing.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\exception
 * @filesource
 */

namespace sabretooth\exception;
use sabretooth\log, sabretooth\util;
use sabretooth\business as bus;

/**
 * missing: missing file exception
 * 
 * This exception is thrown when trying to include a file that doesn't exist
 * @package sabretooth\exception
 */
class missing extends base_exception
{
  /**
   * Constructor
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $file the missing file.
   * @param string|int $context The exceptions context, either a function name or error code.
   * @param exception $previous The previous exception used for the exception chaining.
   * @access public
   */
  public function __construct( $file, $context, $previous = NULL )
  {
    $message = sprintf( 'missing file: "%s"', $file );
    parent::__construct( $message, $context, $previous );
  }
}
?>
