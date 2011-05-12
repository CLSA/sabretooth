<?php
/**
 * ldap.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\exception
 * @filesource
 */

namespace sabretooth\exception;
use sabretooth\log, sabretooth\util;
use sabretooth\business as bus;

/**
 * ldap: ldap file exception
 * 
 * This exception is thrown when trying to include a file that doesn't exist
 * @package sabretooth\exception
 */
class ldap extends base_exception
{
  /**
   * Constructor
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $file the ldap file.
   * @param string|int $context The exceptions context, either a function name or error code.
   * @param exception $previous The previous exception used for the exception chaining.
   * @access public
   */
  public function __construct( $message, $context, $previous = NULL )
  {
    // If the error code is negative then add 99000 and make it positive
    // This way a -9 would appear as 399009 instead of 299991
    parent::__construct( $message, 99000 + abs( $context ), $previous );
  }

  /**
   * Returns whether the exception was thrown because of trying to create a user that already
   * exists.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return boolean
   * @access public
   */
  public function is_already_exists()
  {
    return LDAP_BASE_ERROR_NUMBER + 68 == $this->get_number();
  }
}
?>
