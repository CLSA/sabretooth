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

    if( class_exists( 'sabretooth\session' ) && \sabretooth\session::self()->is_initialized() )
    {
      $user_name = \sabretooth\session::self()->get_user()->name;
      $role_name = \sabretooth\session::self()->get_role()->name;
      $site_name = \sabretooth\session::self()->get_site()->name;
      $who = "$user_name:$role_name@$site_name";
    }
    
    // determine the error number
    $code = 0;
    
    // try and determine the error type base code
    $constant_name = strtoupper( $this->get_type() ).'_BASE_ERROR_NUMBER';
    $base_code = defined( $constant_name ) ? constant( $constant_name ) : 0;

    if( is_numeric( $context ) )
    { // pre-defined error code, add the type code to it
      $code = $base_code + $context;
    }
    else if( is_string( $context ) )
    {
      // in case this is a method name then we need to replace :: with __
      $context = str_replace( '::', '__', $context );

      // remove namespaces
      $index = strrchr( $context, '\\' );
      if( false !== $index ) $context = substr( $index, 1 );

      $constant_name = strtoupper( sprintf( '%s_%s_ERROR_NUMBER',
                                   $this->get_type(),
                                   $context ) );
      $code = defined( $constant_name ) ? constant( $constant_name ) : $base_code;
    }
    
    $this->error_number_constant_name = $constant_name;
    parent::__construct( "$constant_name ($code) : $who : $message", $code, $previous );
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
  { return \sabretooth\util::convert_number_to_code( $this->get_number() ); }

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

  /**
   * The name of the error number constant defining this widget
   * @var string
   * @access private
   */
  private $error_number_constant_name;
}
?>
