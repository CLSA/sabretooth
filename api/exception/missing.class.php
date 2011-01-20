<?php
/**
 * missing.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\exception
 */

namespace sabretooth\exception;

/**
 * missing: missing file exception
 * 
 * This exception is thrown when trying to include a file that doesn't exist
 * @package sabretooth\exception
 */
class missing extends base_exception
{
  /**
   * Constructor
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $file the missing file.
   * @param database\site $site the associated site (if null then the session's site is used)
   * @param database\user $user the associated user (if null then the session's site is used)
   * @param exception $previous the previous exception used for the exception chaining
   * @access public
   */
  public function __construct( $file, $site = NULL, $user = NULL, $previous = NULL )
  {
    parent::__construct( 'missing file: "'.$file, $site, $user, $previous );
  }
}
?>
