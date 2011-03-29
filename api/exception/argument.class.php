<?php
/**
 * argument.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\exception
 * @filesource
 */

namespace sabretooth\exception;

/**
 * argument: bad or missing argument exception
 *
 * This exception is thrown anytime a function or method is expecting an argument which are invalid
 * or missing.
 * @package sabretooth\exception
 */
class argument extends base_exception
{
  /**
   * Constructor
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $argument_name The name of the argument which is invalid.
   * @param mixed $value The value of the argument which is invalid.
   * @param string|int $context The exceptions context, either a function name or error code.
   * @param exception $previous The previous exception used for the exception chaining.
   * @access public
   */
  public function __construct( $argument_name, $value, $context, $previous = NULL )
  {
    $this->argument_name = $argument_name;
    $message = sprintf( 'Invalid argument "%s" with value "%s".',
                        $this->argument_name,
                        \sabretooth\util::var_dump( $value ) );
    parent::__construct( $message, $context, $previous );
  }
  
  /**
   * The name of the missing argument.
   * @var string
   * @access protected
   */
  protected $argument_name = NULL;
}
?>
