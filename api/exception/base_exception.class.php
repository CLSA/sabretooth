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
   * @param exception $previous the previous exception used for the exception chaining
   * @access public
   */
  public function __construct( $message, $previous = NULL )
  {
    $who = 'unknown';

    if( class_exists( 'sabretooth\session' ) )
    {
      $user_name = \sabretooth\session::self()->get_user()->name;
      $role_name = \sabretooth\session::self()->get_role()->name;
      $site_name = \sabretooth\session::self()->get_site()->name;
      $who = "$user_name:$role_name@$site_name";
    }
    
    parent::__construct( "\n$who\n$message", 0, $previous );
  }
  
  /**
   * Get the exception as a string.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return string
   * @access public
   */
  public function to_string() { return $this->__toString(); }

  /**
   * Get the exception message.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return string
   * @access public
   */
  public function get_message() { return $this->getMessage(); }
}
?>
