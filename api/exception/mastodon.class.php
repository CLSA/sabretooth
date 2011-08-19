<?php
/**
 * mastodon.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\exception
 * @filesource
 */

namespace sabretooth\exception;
use sabretooth\log, sabretooth\util;
use sabretooth\business as bus;

/**
 * mastodon: mastodon exceptions
 * 
 * This is a special exception that is used to duplicate an exception received from mastodon.
 * @package sabretooth\exception
 */
class mastodon extends base_exception
{
  /**
   * Constructor
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string|exception $message A message describing the exception or, if an exception,
                             the raw message from that exception will be used instead.
   * @param string|int $context The exceptions context, either a function name or error code.
   * @param exception $previous The previous exception used for the exception chaining.
   * @access public
   */
  public function __construct( $type, $code, $message, $previous = NULL )
  {
    parent::__construct( $message, 0, $previous );
    $this->code = $code;
  }

  /**
   * Overrides the parent method since we are using mastodon's error codes.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return int
   * @access public
   */
  public function get_number() { return 0; }

  /**
   * Overrides the parent method since we are using mastodon's error codes.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return string
   * @access public
   */
  public function get_code()
  {
    return $this->code;
  }
  
  protected $code;
}
?>
