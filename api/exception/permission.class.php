<?php
/**
 * permission.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\exception
 */

namespace sabretooth\exception;
require_once $SETTINGS[ 'api_path' ].'/exception/base_exception.class.php';

/**
 * permission: permission exceptions
 * 
 * All exceptions which are due to denied permissions, use this class to throw exceptions.
 * @package sabretooth\exception
 */
class permission extends base_exception
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
