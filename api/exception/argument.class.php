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
   * @author TBD
   * @access public
   */
  public function __construct( $argument_name, $value, $previous = NULL )
  {
    // get the 
    $this->argument_name = $argument_name;
    parent::__construct(
      sprintf( 'Invalid argument "%s" with value "%s".',
               $this->argument_name,
               \sabretooth\util::var_dump( $value ) ),
      $previous );
  }
  
  /**
   * The name of the missing argument.
   * @var string
   * @access protected
   */
  protected $argument_name = NULL;
}
?>
