<?php
/**
 * cenozo_service.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\exception
 * @filesource
 */

namespace sabretooth\exception;
use sabretooth\log, sabretooth\util;
use sabretooth\business as bus;

/**
 * cenozo_service: cenozo service exceptions
 * 
 * This exception is used to duplicate an exception received from another cenozo application.
 * @package sabretooth\exception
 */
class cenozo_service extends base_exception
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
    parent::__construct( sprintf( '[%s] %s', $code, $message ), 0, $previous );
    $this->code = $code;
  }

  /**
   * Overrides the parent method since we are using another cenozo application's error codes.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return int
   * @access public
   */
  public function get_number() { return 0; }

  /**
   * Overrides the parent method since we are using another cenozo application's error codes.
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
