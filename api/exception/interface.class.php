<?php
/**
 * interface.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\exception
 */

namespace sabretooth\exception;

/**
 * interface: user-interface exceptions
 *
 * All exceptions which occur in the user-interface, whether from the web-interface or elsewhere,
 * use this class to throw exceptions.
 * @package sabretooth\exception
 */
class interface extends base_exception
{
  /**
   * Constructor
   * @author TBD
   * @access public
   */
   public function __construct( $message = "", $code = 0, $previous = NULL )
   {
     parent::__construct( $message, $code, $previous );
   }
}
?>
