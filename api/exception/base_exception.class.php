<?php
/**
 * base_exception.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\exception
 */

namespace sabretooth\exception;

/**
 * base_exception: base exception class
 *
 * The base_exception class from which all other sabretooth exceptions extend
 * @package sabretooth\exception
 */
class base_exception extends \Exception
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
