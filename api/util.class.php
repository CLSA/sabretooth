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
      // in case this is a method name then we need to replace :: with __
      $context = str_replace( '::', '__', $context );

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
   * Returns timezone abbreviations
   * 
   * @author Patrick Emond <emondpd@mcamster.ca>
   * @param string $timezone
   * @return string
   * @static
   * @access public
   */
  public static function get_timezone_abbreviation( $timezone )
  {
    if( 'Canada/Pacific' == $timezone ) return 'PST';
    else if( 'Canada/Mountain' == $timezone ) return 'MST';
    else if( 'Canada/Central' == $timezone ) return 'CST';
    else if( 'Canada/Eastern' == $timezone ) return 'EST';
    else if( 'Canada/Atlantic' == $timezone ) return 'AST';
    else if( 'Canada/Newfoundland' == $timezone ) return 'NST';
    else '???';
  }
  
  /**
   * Converts the server's date to a user's date
   * 
   * @author Patrick Emond <emondpd@mcamster.ca>
   * @param string $date A date string in yyyy-mm-dd hh:mm:dd format
   * @return string
   * @static
   * @access public
   */
  public static function from_server_date( $date )
  {
    if( is_null( $date ) || !is_string( $date ) ) return $date;

    $user_tz = \sabretooth\session::self()->get_site()->timezone;
    $server_tz = date( 'e' );

    $date_obj = new \DateTime( $date, new \DateTimeZone( $server_tz ) );
    $date_obj->setTimeZone( new \DateTimeZone( $user_tz ) );
    return $date_obj->format( "Y-m-d G:i:s" );
  }

  /**
   * Converts a user's date to server date.
   * 
   * @author Patrick Emond <emondpd@mcamster.ca>
   * @param string $date A date string in yyyy-mm-dd hh:mm:dd format
   * @return string
   * @static
   * @access public
   */
  public static function to_server_date( $date )
  {
    if( is_null( $date ) || !is_string( $date ) ) return $date;

    $user_tz = \sabretooth\session::self()->get_site()->timezone;
    $server_tz = date( 'e' );

    $date_obj = new \DateTime( $date, new \DateTimeZone( $user_tz ) );
    $date_obj->setTimeZone( new \DateTimeZone( $server_tz ) );
    return $date_obj->format( "Y-m-d G:i:s" );
  }

  /**
   * Converts the server's time to a user's time
   * 
   * @author Patrick Emond <emondpd@mcamster.ca>
   * @param string $time A time string in hh:mm or hh:mm:ss
   * @return string
   * @static
   * @access public
   */
  public static function from_server_time( $time )
  {
    if( is_null( $time ) || !is_string( $time ) ) return $time;

    $user_tz = \sabretooth\session::self()->get_site()->timezone;
    $server_tz = date( 'e' );

    $time_obj = new \DateTime( $time, new \DateTimeZone( $server_tz ) );
    $time_obj->setTimeZone( new \DateTimeZone( $user_tz ) );
    return $time_obj->format( "G:i:s" );
  }

  /**
   * Converts a user's time to server time.
   * 
   * @author Patrick Emond <emondpd@mcamster.ca>
   * @param string $time A time string in hh:mm or hh:mm:ss
   * @return string
   * @static
   * @access public
   */
  public static function to_server_time( $time )
  {
    if( is_null( $time ) || !is_string( $time ) ) return $time;

    $user_tz = \sabretooth\session::self()->get_site()->timezone;
    $server_tz = date( 'e' );

    $time_obj = new \DateTime( $time, new \DateTimeZone( $user_tz ) );
    $time_obj->setTimeZone( new \DateTimeZone( $server_tz ) );
    return $time_obj->format( "G:i:s" );
  }

  /**
   * Returns the date as a user-friendly string.
   * 
   * @author Patrick Emond <emondpd@mcamster.ca>
   * @param string $date A date string in the format accepted by the DateTime constructor.
   * @return string
   * @static
   * @access public
   */
  public static function get_formatted_date( $date )
  {
    if( is_null( $date ) || !is_string( $date ) ) return 'unknown';

    $date_obj = new \DateTime( $date );
    return $date_obj->format( 'l, F jS, Y' );
  }

  /**
   * Returns the time as a user-friendly string.
   * 
   * @author Patrick Emond <emondpd@mcamster.ca>
   * @param string $date A date string in the format accepted by the DateTime constructor.
   * @return string
   * @static
   * @access public
   */
  public static function get_formatted_time( $time, $include_seconds = true )
  {
    if( is_null( $time ) || !is_string( $time ) ) return 'unknown';

    $user_tz = \sabretooth\session::self()->get_site()->timezone;
    $time_obj = new \DateTime( $time, new \DateTimeZone( $user_tz ) );
    return $time_obj->format( $include_seconds ? 'g:i:s A, T' : 'g:i A, T' );
  }

  /**
   * Returns a fuzzy description of how long ago a certain date occured.
   * 
   * @author Patrick Emond <emondpd@mcamster.ca>
   * @param string $date A date string in the format accepted by the DateTime constructor.
   * @return string
   * @static
   * @access public
   */
  public static function get_fuzzy_period_ago( $date )
  {
    if( is_null( $date ) || !is_string( $date ) ) return 'never';
    
    // we need to convert to server time since we will compare to the server's "now" time
    $date = new \DateTime( self::to_server_time( $date ) );
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
    // special cases
    if( 'access' == $word )
    {
      return $word;
    }
    
    if( 'qnaire' == $word )
    {
      return 'questionnaires';
    }

    if( 'survey' == $word )
    {
      return 'surveys';
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
   * Get the foreground color of the flap given a jquery-ui theme.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $theme The name of a jquery theme.
   * @static
   * @access public
   */
  public static function get_flap_css_color( $theme )
  {
    if( 'black-tie' == $theme ) $color = '#eeeeee';
    else if( 'blitzer' == $theme ) $color = '#ffffff';
    else if( 'cupertino' == $theme ) $color = '#222222';
    else if( 'dark-hive' == $theme ) $color = '#ffffff';
    else if( 'dot-luv' == $theme ) $color = '#f6f6f6';
    else if( 'eggplant' == $theme ) $color = '#ffffff';
    else if( 'excite-bike' == $theme ) $color = '#e69700';
    else if( 'flick' == $theme ) $color = '#444444';
    else if( 'hot-sneaks' == $theme ) $color = '#e1e463';
    else if( 'humanity' == $theme ) $color = '#ffffff';
    else if( 'le-frog' == $theme ) $color = '#ffffff';
    else if( 'mint-choc' == $theme ) $color = '#e3ddc9';
    else if( 'overcast' == $theme ) $color = '#444444';
    else if( 'pepper-grinder' == $theme ) $color = '#453821';
    else if( 'redmond' == $theme ) $color = '#ffffff';
    else if( 'smoothness' == $theme ) $color = '#222222';
    else if( 'south-street' == $theme ) $color = '#433f38';
    else if( 'start' == $theme ) $color = '#eaf5f7';
    else if( 'sunny' == $theme ) $color = '#ffffff';
    else if( 'swanky-purse' == $theme ) $color = '#eacd86';
    else if( 'trontastic' == $theme ) $color = '#222222';
    else if( 'ui-darkness' == $theme ) $color = '#ffffff';
    else if( 'ui-lightness' == $theme ) $color = '#ffffff';
    else if( 'vader' == $theme ) $color = '#ffffff';
    else $color = '#ffffff';

    return $color;
  }

  /**
   * Get the background color of the flap given a jquery-ui theme.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $theme The name of a jquery theme.
   * @static
   * @access public
   */
  public static function get_flap_css_background( $theme )
  {
    if( 'black-tie' == $theme ) $background = '#333333';
    else if( 'blitzer' == $theme ) $background = '#cc0000';
    else if( 'cupertino' == $theme ) $background = '#deedf7';
    else if( 'dark-hive' == $theme ) $background = '#444444';
    else if( 'dot-luv' == $theme ) $background = '#0b3e6f';
    else if( 'eggplant' == $theme ) $background = '#30273a';
    else if( 'excite-bike' == $theme ) $background = '#f9f9f9';
    else if( 'flick' == $theme ) $background = '#dddddd';
    else if( 'hot-sneaks' == $theme ) $background = '#35414f';
    else if( 'humanity' == $theme ) $background = '#cb842e';
    else if( 'le-frog' == $theme ) $background = '#3a8104';
    else if( 'mint-choc' == $theme ) $background = '#453326';
    else if( 'overcast' == $theme ) $background = '#dddddd';
    else if( 'pepper-grinder' == $theme ) $background = '#ffffff';
    else if( 'redmond' == $theme ) $background = '#5c9ccc';
    else if( 'smoothness' == $theme ) $background = '#cccccc';
    else if( 'south-street' == $theme ) $background = '#ece8da';
    else if( 'start' == $theme ) $background = '#2191c0';
    else if( 'sunny' == $theme ) $background = '#817865';
    else if( 'swanky-purse' == $theme ) $background = '#261803';
    else if( 'trontastic' == $theme ) $background = '#9fda58';
    else if( 'ui-darkness' == $theme ) $background = '#333333';
    else if( 'ui-lightness' == $theme ) $background = '#f6a828';
    else if( 'vader' == $theme ) $background = '#888888';
    else $background = 'white';

    return $background;
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
