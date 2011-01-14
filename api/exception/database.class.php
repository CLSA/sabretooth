<?php
/**
 * database.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\exception
 */

namespace sabretooth\exception;
require_once API_PATH.'/exception/base_exception.class.php';

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
   * @author TBD
   * @access public
   */
   public function __construct( $message = "", $code = 0, $previous = NULL )
   {
     parent::__construct( $message, $code, $previous );
   }
}
?>
