<?php
/**
 * ui.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\exception
 */

namespace sabretooth\exception;

/**
 * ui: user-ui exceptions
 *
 * All exceptions which occur in the user-ui, whether from the web-ui or elsewhere,
 * use this class to throw exceptions.
 * @package sabretooth\exception
 */
class ui extends base_exception
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
