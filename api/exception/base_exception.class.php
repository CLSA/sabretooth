<?php
/**
 * base_exception.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\exception
 * @filesource
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
   * @param string $message A message describing the exception.
   * @param string|int $context The exceptions context, either a function name or error code.
   * @param exception $previous The previous exception used for the exception chaining.
   * @access public
   */
  public function __construct( $message, $context, $previous = NULL )
  {
    $who = 'unknown';

    if( class_exists( 'sabretooth\session' ) )
    {
      $user_name = \sabretooth\session::self()->get_user()->name;
      $role_name = \sabretooth\session::self()->get_role()->name;
      $site_name = \sabretooth\session::self()->get_site()->name;
      $who = "$user_name:$role_name@$site_name";
    }
    
    $code = \sabretooth\util::get_error_number( $this->get_type(), $context );
    parent::__construct( "\n$who\n$message", $code, $previous );
  }
  
  /**
   * Returns the type of exception as a string.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return string
   * @access public
   */
  public function get_type() { return substr( strrchr( get_called_class(), '\\' ), 1 ); }

  /**
   * Get the exception as a string.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return string
   * @access public
   */
  public function to_string() { return $this->__toString(); }

  /**
   * Returns the exception's error number.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return int
   * @access public
   */
  public function get_number() { return $this->getCode(); }

  /**
   * Returns the exception's error code (the error number as an encoded string)
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return string
   * @access public
   */
  public function get_code()
  { return preg_replace( '/^([0-9]+)([0-9]{3})/', '$1.$2', $this->get_number() ); }

  /**
   * Get the exception message.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return string
   * @access public
   */
  public function get_message() { return $this->getMessage(); }

  /**
   * Get the exception backtrace.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return string
   * @access public
   */
  public function get_backtrace() { return $this->getTraceAsString(); }
}
?>
