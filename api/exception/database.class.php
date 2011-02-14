<?php
/**
 * database.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\exception
 * @filesource
 */

namespace sabretooth\exception;

/**
 * database: database/sql exceptions
 *
 * All exceptions which are due to the database, including connection errors and queries, use this
 * class to throw exceptions.
 * @package sabretooth\exception
 */
class database extends base_exception
{
  /**
   * Constructor
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $message A message describing the exception.
   * @param string $sql The SQL statement which caused the exception.
   * @param string|int $context The exceptions context, either a function name or error code.
   * @param exception $previous The previous exception used for the exception chaining.
   * @access public
   */
  public function __construct( $message, $sql = NULL, $context, $previous = NULL )
  {
    $this->sql = $sql;
    $message .= is_null( $this->sql ) ? '' : "\n$sql";
    parent::__construct( $message, $context, $previous );
  }
    
  /**
   * The sql which caused the exception.
   * @var string
   * @access protected
   */
  protected $sql = NULL;
}
?>
