<?php
/**
 * util.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth
 * @filesource
 */

namespace sabretooth;

/**
 * util: utility class of static methods
 *
 * This class is where all utility functions belong.  The class is final so that it cannot be
 * instantiated nor extended (and it shouldn't be!).  All methods within the class are static.
 * NOTE: only functions which do not logically belong in any class should be included here.
 * @package sabretooth
 */
final class util
{
  /**
   * Constructor (or not)
   * 
   * This method is kept private so that no one ever tries to instantiate it.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access private
   */
  private function __construct() {}

  /**
   * Returns whether the system is in development mode.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @static
   * @return boolean
   * @access public
   */
  public static function in_devel_mode()
  {
    return true == session::self()->get_setting( 'general', 'development_mode' );
  }

  /**
   * Returns whether the system is in action mode.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @static
   * @return boolean
   * @access public
   */
  public static function in_action_mode()
  {
    if( is_null( self::$action_mode ) )
      self::$action_mode = 'action.php' == session::self()->get_setting( 'general', 'script_name' );
    
    return self::$action_mode;
  }
  
  /**
   * Returns whether the system is in widget mode.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @static
   * @return boolean
   * @access public
   */
  public static function in_widget_mode()
  {
    if( is_null( self::$widget_mode ) )
      self::$widget_mode = 'widget.php' == session::self()->get_setting( 'general', 'script_name' );
    
    return self::$widget_mode;
  }
  
  /**
   * Returns the elapsed time in seconds since the script began.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return float
   * @static
   * @access public
   */
  public static function get_elapsed_time()
  {
    return microtime( true ) - $_SESSION['time']['script_start_time'];
  }

  /**
   * Gets the name of an error constant given the type and context.
   * 
   * There are three types of error codes.  Those that occur inside of method, those that occur
   * inside of a function and those which have pre-defined error codes.
   * For methods, the $context argument should be the pre-defined __METHOD__ constant.
   * For functions, the $context argument should be the pre-defined __FUNCTION__ constant.
   * For pre-defined error codes the $context argument should be the code as an integer.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $type The exception related to this error (ie: argument, database, missing, etc)
   * @param string|int $class The class or function code where the error occurs.
   * @param string $method The method or function name where the error occurs.
   * @return int
   * @static
   * @access public
   */
  public static function get_error_number( $type, $context )
  {
    $code = 0;
    
    // try and determine the error type base code
    $name = strtoupper( $type ).'_BASE_ERROR_NUMBER';
    $base_code = defined( $name ) ? constant( $name ) : 0;

    if( is_numeric( $context ) )
    { // pre-defined error code, add the type code to it
      $code = $base_code + $context;
    }
    else if( is_string( $context ) )
    {
      // in case this is a method name then we need to replace :: with _
      $context = str_replace( '::', '_', $context );

      // remove namespaces
      $index = strrchr( $context, '\\' );
      if( false !== $index ) $context = substr( $index, 1 );

      $name = strtoupper( sprintf( '%s_%s_ERROR_NUMBER',
                                   $type,
                                   $context ) );
      $code = defined( $name ) ? constant( $name ) : $base_code;
    }

    return $code;
  }

  /**
   * Returns the result of var_dump()
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param mixed $data The data to dump.
   * @static
   * @access public
   */
  public static function var_dump( $data )
  {
    // get the var_dump string by buffering the output
    ob_start();
    var_dump( $data );
    return ob_get_clean();
  }

  /**
   * An html-enhanced var_dump
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param mixed $data The data to display.
   * @static
   * @access public
   */
  public static function var_dump_html( $data )
  {
    // make strings magenta
    $output = preg_replace( '/("[^"]*")/', '<font color="magenta">${1}</font>', self::var_dump( $data ) );

    // make types yellow and type braces red
    $output = preg_replace(
      '/\n( *)(bool|int|float|string|array|object)\(([^)]*)\)/',
      "\n".'${1}<font color="yellow">${2}</font>'.
      '<font color="red">(</font>'.
      '<font color="white"> ${3} </font>'.
      '<font color="red">)</font>',
      "\n".$output );
      
    // replace => with html arrows
    $output = str_replace( '=>', ' &#8658;', $output );
    
    // output as a pre
    echo '<pre style="font-weight: bold; color: #B0B0B0; background: black">'.$output.'</pre>';
  }

  /**
   * Returns a fuzzy-time description of how long ago a certain date occured.
   * 
   * @author Patrick EMond <emondpd@mcamster.ca>
   * @param string|DateTime $date A DateTime object, or if a string is passed then it is converted
                                  into a DateTime object.
   * @return string
   * @static
   * @access public
   */
  public static function get_fuzzy_time_ago( $date )
  {
    if( is_null( $date ) || !is_string( $date ) ) return 'never';

    $date = new \DateTime( $date );
    $interval = $date->diff( new \DateTime() );
    
    if( 0 != $interval->invert )
    {
      $result = 'in the future';
    }
    else if( 1 > $interval->i && 0 == $interval->h && 0 == $interval->days )
    {
      $result = 'seconds ago';
    }
    else if( 1 > $interval->h && 0 == $interval->days )
    {
      $result = 'minutes ago';
    }
    else if( 1 > $interval->d && 0 == $interval->days )
    {
      $result = 'hours ago';
    }
    else if( 1 == $interval->days )
    {
      $result = 'yesterday';
    }
    else if( 7 > $interval->days )
    {
      $result = 'last '.$date->format( 'l' );
    }
    else if( 1 > $interval->m && 0 == $interval->y )
    {
      $result = 'weeks ago';
    }
    else if( 1 > $interval->y )
    {
      $result = 'last '.$date->format( 'F' );
    }
    else
    {
      $result = 'years ago';
    }

    return $result;
  }
  
  /**
   * Attempts to convert a word into its plural form.
   * 
   * Warning: this method by no means returns the correct answer in every case.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $word
   * @return string
   * @static
   * @access public
   */
  public static function pluralize( $word )
  {
    if( 'access' == $word )
    { // special case
      return $word;
    }
    
    if( 'y' == substr( $word, -1 ) )
    { // likely, any word ending in 'y' has 'ies' at the end of the plural word
      return substr( $word, 0, -1 ).'ies';
    }
    
    if( 's' == substr( $word, -1 ) )
    { // likely, any word ending in an 's' has 'es' at the end of the plural word
      return $word.'es';
    }
    
    // if there is no rule for this word then we hope that adding an 's' at the end is sufficient
    return $word.'s';
  }

  /**
   * Converts an error number into an easier-to-read error code.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param int $number The error number.
   * @return string
   * @static
   * @access public
   */
  public static function convert_number_to_code( $number )
  {
    return preg_replace( '/^([0-9]+)([0-9]{3})/', '$1.$2', $number );
  }

  /**
   * Sends an HTTP error status along with the specified data.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $data The data to send along with the error.
   * @static
   * @access public
   */
  public static function send_http_error( $data )
  {
    \HttpResponse::status( 400 );
    \HttpResponse::setContentType( 'application/json' ); 
    \HttpResponse::setData( $data );
    \HttpResponse::send();
  }

  /**
   * Cache for action_mode method.
   * @var bool
   * @access private
   */
  private static $action_mode = NULL;

  /**
   * Cache for widget_mode method.
   * @var bool
   * @access private
   */
  private static $widget_mode = NULL;
}
?>
