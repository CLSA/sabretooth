<?php
/**
 * util.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth
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
    {
      self::$action_mode = 'action.php' == session::self()->get_setting( 'general', 'script_name' );
      log::debug( "action mode: ".( self::$action_mode ? "true" : "false" ) );
    }
    return self::$action_mode;
  }
  
  /**
   * Cache for action_mode method.
   * @var bool
   * @access private
   */
  private static $action_mode = NULL;

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
    {
      self::$action_mode = 'widget.php' == session::self()->get_setting( 'general', 'script_name' );
      log::debug( "widget mode: ".( self::$widget_mode ? "true" : "false" ) );
    }
    return self::$widget_mode;
  }
  
  /**
   * Cache for widget_mode method.
   * @var bool
   * @access private
   */
  private static $widget_mode = NULL;

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
    // get the var_dump string
    ob_start();
    var_dump( $data );
    $output = ob_get_clean();

    // make strings magenta
    $output = preg_replace( '/("[^"]*")/', '<font color="magenta">${1}</font>', $output );

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
    if( is_string( $date ) ) $date = new \DateTime( $date );
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
}
?>
