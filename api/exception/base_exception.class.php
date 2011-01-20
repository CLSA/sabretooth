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
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $message the error message.
   * @param database\site $site the associated site (if null then the session's site is used)
   * @param database\user $user the associated user (if null then the session's site is used)
   * @param exception $previous the previous exception used for the exception chaining
   * @access public
   */
  public function __construct( $message, $site = NULL, $user = NULL, $previous = NULL )
  {
    if( !is_null( $site ) )
    {
      $this->site = $site;
    }
    else if( class_exists( \sabretooth\session::exists() ) )
    {
      $this->site = \sabretooth\session::singleton()->get_site();
    }
    
    if( !is_null( $user ) )
    {
      $this->user = $user;
    }
    else if( class_exists( \sabretooth\session::exists() ) )
    {
      $this->user = \sabretooth\session::singleton()->get_user();
    }
    
    parent::__construct( "\n".
      ( $site ? $site->name : 'unknown' ).'@'.( $user ? $user->name : 'unknown' ).': '.$message,
      0,
      $previous );
  }
  
  /**
   * Get the exception message.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return string
   * @access public
   */
  public function get_message() { return $this->getMessage(); }

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
}
?>
