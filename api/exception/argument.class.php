<?php
/**
 * argument.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\exception
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
  public function __construct( $argument_name, $previous = NULL )
  {
    $this->argument_name = $argument_name;
    parent::__construct( "Invalid or missing argument \"$this->argument_name\"", $previous );
  }
  
  /**
   * The name of the missing argument.
   * @var string
   * @access protected
   */
  protected $argument_name = NULL;
}
?>
