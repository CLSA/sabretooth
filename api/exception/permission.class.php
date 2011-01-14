<?php
/**
 * permission.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\exception
 */

namespace sabretooth\exception;
require_once API_PATH.'/exception/base_exception.class.php';

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
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\operation $operation the associated operation
   * @param database\site $site the associated site (if null then the session's site is used)
   * @param database\user $user the associated user (if null then the session's site is used)
   * @param exception $previous the previous exception used for the exception chaining
   * @access public
   */
  public function __construct( $operation, $site = NULL, $user = NULL, $previous = NULL )
  {
    $this->operation = $operation;
    parent::__construct( 'operation "'.$operation->name.'" denied', $site, $user, $previous );
  }

  /**
   * The site for which the denied operation was executed on
   * @var database\site
   * @access protected
   */
  protected $site = NULL;

  /**
   * The user which executed the denied operation
   * @var database\site
   * @access protected
   */
  protected $user = NULL;

  /**
   * The operation which was denied
   * @var database\site
   * @access protected
   */
  protected $operation = NULL;
}
?>
