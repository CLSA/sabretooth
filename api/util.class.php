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
    return true == business\setting_manager::self()->get_setting( 'general', 'development_mode' );
  }

  /**
   * Returns whether the system is in pull mode.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @static
   * @return boolean
   * @access public
   */
  public static function in_pull_mode()
  {
    if( is_null( self::$pull_mode ) )
      self::$pull_mode =
        'pull' == business\setting_manager::self()->get_setting( 'general', 'operation_type' );
    
    return self::$pull_mode;
  }
  
  /**
   * Returns whether the system is in push mode.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @static
   * @return boolean
   * @access public
   */
  public static function in_push_mode()
  {
    if( is_null( self::$push_mode ) )
      self::$push_mode =
        'push' == business\setting_manager::self()->get_setting( 'general', 'operation_type' );
    
    return self::$push_mode;
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
      self::$widget_mode =
        'widget' == business\setting_manager::self()->get_setting( 'general', 'operation_type' );
    
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
   * Returns a DateTimeZone object for the user's current site's timezone
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param boolean $server Whether to return the application's or server's timezone
   * @return DateTimeZone
   * @access public
   */
  public static function get_timezone_object( $server = false )
  {
    $db_site = business\session::self()->get_site();
    return new \DateTimeZone( $server || !$db_site ? 'UTC' : $db_site->timezone );
  }

  /**
   * Returns a DateTime object in the user's current site's timezone
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $datetime A date string in any valid PHP date time format.
   * @param boolean $server Whether to return the datetimein the application's or server's timezone
   * @return DateTime
   * @access public
   */
  public static function get_datetime_object( $datetime = NULL, $server = false )
  {
    return new \DateTime( $datetime, self::get_timezone_object( $server ) );
  }

  /**
   * Converts the server's date/time to a user's date/time
   * 
   * @author Patrick Emond <emondpd@mcamster.ca>
   * @param string $datetime A date string in any valid PHP date time format.
   * @param string $format The format to return the date/time in (default 'Y-m-d H:i:s')
   * @return string
   * @static
   * @access public
   */
  public static function from_server_datetime( $datetime, $format = 'Y-m-d H:i:s' )
  {
    if( is_null( $datetime ) || !is_string( $datetime ) ) return $datetime;

    $datetime_obj = self::get_datetime_object( $datetime, true ); // server's timezone
    $datetime_obj->setTimeZone( self::get_timezone_object() );
    return $datetime_obj->format( $format );
  }

  /**
   * Converts a user's date to server date.
   * 
   * @author Patrick Emond <emondpd@mcamster.ca>
   * @param string $datetime A date string in any valid PHP date time format.
   * @param string $format The format to return the date/time in (default 'Y-m-d H:i:s')
   * @return string
   * @static
   * @access public
   */
  public static function to_server_datetime( $datetime, $format = 'Y-m-d H:i:s' )
  {
    if( is_null( $datetime ) || !is_string( $datetime ) ) return $datetime;

    $datetime_obj = self::get_datetime_object( $datetime );
    $datetime_obj->setTimeZone( self::get_timezone_object( true ) );
    return $datetime_obj->format( $format );
  }

  /**
   * Returns the date and time as a user-friendly string.
   * 
   * @author Patrick Emond <emondpd@mcamster.ca>
   * @param string $datetime A date string in any valid PHP date time format.
   * @param boolean $include_seconds Whether to include the seconds in the output
   * @param string $invalid What to return if the input is invalid.
   * @return string
   * @static
   * @access public
   */
  public static function get_formatted_datetime(
    $datetime, $include_seconds = true, $invalid = 'unknown' )
  {
    if( is_null( $datetime ) || !is_string( $datetime ) ) return $invalid;

    $time_obj = self::get_datetime_object( $datetime );
    return $time_obj->format( 'Y-m-d '.( $include_seconds ? 'g:i:s A, T' : 'g:i A, T' ) );
  }

  /**
   * Returns the date as a user-friendly string.
   * 
   * @author Patrick Emond <emondpd@mcamster.ca>
   * @param string $date A date string in any valid PHP date time format.
   * @param string $invalid What to return if the input is invalid.
   * @return string
   * @static
   * @access public
   */
  public static function get_formatted_date( $date, $invalid = 'unknown' )
  {
    if( is_null( $date ) || !is_string( $date ) ) return $invalid;

    $datetime_obj = self::get_datetime_object( $date );
    return $datetime_obj->format( 'l, F jS, Y' );
  }

  /**
   * Returns the time as a user-friendly string.
   * 
   * @author Patrick Emond <emondpd@mcamster.ca>
   * @param string $time A time string in any valid PHP date time format.
   * @param boolean $include_seconds Whether to include the seconds in the output
   * @param string $invalid What to return if the input is invalid.
   * @return string
   * @static
   * @access public
   */
  public static function get_formatted_time( $time, $include_seconds = true, $invalid = 'unknown' )
  {
    if( is_null( $time ) || !is_string( $time ) ) return $invalid;

    $time_obj = self::get_datetime_object( $time );
    return $time_obj->format( $include_seconds ? 'g:i:s A, T' : 'g:i A, T' );
  }

  /**
   * Returns the interval between the date and "now"
   * 
   * @author Patrick Emond <emondpd@mcamster.ca>
   * @param string $date A date string in any valid PHP date time format.
   * @param string $date2 A second string to compare to instead of "now"
   * @return \DateInterval
   * @static
   * @access public
   */
  public static function get_interval( $date, $date2 = NULL )
  {
    // we need to convert to server time since we will compare to the server's "now" time
    $datetime_obj = is_object( $date ) ? $date : self::get_datetime_object( $date );
    $date2_obj = is_object( $date2 ) ? $date2 : self::get_datetime_object( $date2 );
    return $datetime_obj->diff( $date2_obj );
  }

  /**
   * Returns a fuzzy description of how long ago a certain date occured.
   * 
   * @author Patrick Emond <emondpd@mcamster.ca>
   * @param string $datetime A datetime string in any valid PHP date time format.
   * @return string
   * @static
   * @access public
   */
  public static function get_fuzzy_period_ago( $datetime )
  {
    if( is_null( $datetime ) || !is_string( $datetime ) ) return 'never';
    
    $interval = self::get_interval( $datetime );
    
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
      $datetime_obj = self::get_datetime_object( $datetime );
      $result = 'last '.$datetime_obj->format( 'l' );
    }
    else if( 1 > $interval->m && 0 == $interval->y )
    {
      $result = 'weeks ago';
    }
    else if( 1 > $interval->y )
    {
      $datetime_obj = self::get_datetime_object( $datetime );
      $result = 'last '.$datetime_obj->format( 'F' );
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
   * Get the foreground color of a jquery-ui theme.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $theme The name of a jquery theme.
   * @static
   * @access public
   */
  public static function get_foreground_color( $theme )
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
   * Get the background color of a jquery-ui theme.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $theme The name of a jquery theme.
   * @static
   * @access public
   */
  public static function get_background_color( $theme )
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
   * Encodes a string using a SHA1 hash.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $string The string to encode
   * @return string
   * @static
   * @access public
   */
  public static function sha1_hash( $string )
  {
    return '{SHA}'.base64_encode( pack( 'H*', sha1( $string ) ) );
  }

  /**
   * Encodes a string using a MD5 hash.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $string The string to encode
   * @return string
   * @static
   * @access public
   */
  public static function md5_hash( $string )
  {
    return '{MD5}'.base64_encode( pack( 'H*', md5( $string ) ) );
  }

  /**
   * Encodes a string using a NTLM hash.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $string The string to encode
   * @return string
   * @static
   * @access public
   */
  public static function ntlm_hash( $string )
  {
    // Convert the password from UTF8 to UTF16 (little endian), encrypt with the MD4 hash and
    // make it uppercase (not necessary, but it's common to do so with NTLM hashes)
    return strtoupper( hash( 'md4', iconv( 'UTF-8', 'UTF-16LE', $string ) ) );
  }

  /**
   * Encodes a string using a LM hash.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $string The string to encode
   * @return string
   * @static
   * @access public
   */
  public static function lm_hash( $string )
  {
    $string = strtoupper( substr( $string, 0, 14 ) );

    $part_1 = self::des_encrypt( substr( $string, 0, 7 ) );
    $part_2 = self::des_encrypt( substr( $string, 7, 7 ) );

    return strtoupper( $part_1.$part_2 );
  }

  /**
   * Encrypts a string using the DES standard
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $string The string to encode
   * @return string
   * @static
   * @access public
   */
  public static function des_encrypt( $string )
  {
    $key = array();
    $tmp = array();
    $length = strlen( $string );

    for( $i = 0; $i < 7; ++$i ) $tmp[] = $i < $length ? ord( $string[$i] ) : 0;

    $key[] = $tmp[0] & 254;
    $key[] = ( $tmp[0] << 7 ) | ( $tmp[1] >> 1 );
    $key[] = ( $tmp[1] << 6 ) | ( $tmp[2] >> 2 );
    $key[] = ( $tmp[2] << 5 ) | ( $tmp[3] >> 3 );
    $key[] = ( $tmp[3] << 4 ) | ( $tmp[4] >> 4 );
    $key[] = ( $tmp[4] << 3 ) | ( $tmp[5] >> 5 );
    $key[] = ( $tmp[5] << 2 ) | ( $tmp[6] >> 6 );
    $key[] = $tmp[6] << 1;
   
    $key0 = '';
   
    foreach( $key as $k ) $key0 .= chr( $k );
    $crypt = mcrypt_encrypt(
      MCRYPT_DES, $key0, 'KGS!@#$%', MCRYPT_MODE_ECB,
      mcrypt_create_iv( mcrypt_get_iv_size( MCRYPT_DES, MCRYPT_MODE_ECB ), MCRYPT_RAND ) );

    return bin2hex( $crypt );
  }

  /**
   * Cache for pull_mode method.
   * @var bool
   * @access private
   */
  private static $pull_mode = NULL;

  /**
   * Cache for push_mode method.
   * @var bool
   * @access private
   */
  private static $push_mode = NULL;

  /**
   * Cache for widget_mode method.
   * @var bool
   * @access private
   */
  private static $widget_mode = NULL;
}
?>
