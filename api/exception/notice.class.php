<?php
/**
 * notice.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\exception
 * @filesource
 */

namespace sabretooth\exception;

/**
 * notice: notice exceptions
 * 
 * This is a special exception that is used to directly report to the user.
 * The unaltered error message will be noticeed to the user.  If there is a previous exception
 * its error code will also be noticeed.
 * @package sabretooth\exception
 */
class notice extends base_exception
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
    $this->notice = $message;
    parent::__construct( $message, $context, $previous );
  }

  /**
   * Get the notice meant for the end-user.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return string
   * @access public
   */
  public function get_notice() { return $this->notice; }

  protected $notice = '';
}
?>
