<?php
/**
 * log.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth
 * @filesource
 */

namespace sabretooth;

/**
 * @category external
 */
require_once 'Log.php';
require_once 'FirePHPCore/FirePHP.class.php';

/**
 * log: handles all logging
 *
 * The log class is used to log to various outputs depending on the application's running mode.
 * There are several logging functions, each of which have their purpose.  Use this class as
 * follows:
 * <code>
 * log::err( "There is an error here." );
 * log::emerg( "The server is on fire!!" );
 * </code>
 * @package sabretooth
 */
final class log extends singleton
{
  /**
   * Constructor.
   * 
   * Since this class uses the singleton pattern the constructor is never called directly.  Instead
   * use the {@link self} method.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access protected
   */
  protected function __construct()
  {
    $this->loggers[ 'display' ] = NULL;
    $this->loggers[ 'file' ] = NULL;
    $this->loggers[ 'firebug' ] = NULL;
  }

  /**
   * Logging method
   * 
   * This is the highest severity log.  It should be used to describe a major problem which needs
   * to be brought to administrators' attention ASAP (ie: use it sparingly).
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $message The message to log.
   * @static
   * @access public
   */
  public static function emerg( $message ) { self::self()->send( $message, PEAR_LOG_EMERG ); }

  /**
   * Logging method
   * 
   * This is the second highest severity log.  It should be used to describe a major problem which
   * needs to be brought to administrators' attention in the near future (ie: use it sparingly).
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $message The message to log.
   * @static
   * @access public
   */
  public static function alert( $message ) { self::self()->send( $message, PEAR_LOG_ALERT ); }

  /**
   * Logging method
   * 
   * Use this type of log when there is a problem that is more severe than a usual error, but not
   * severe enough to notify administrators.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $message The message to log.
   * @static
   * @access public
   */
  public static function crit( $message ) { self::self()->send( $message, PEAR_LOG_CRIT ); }

  /**
   * Logging method
   * 
   * Use this type of log when there is an error.  For very severe errors see {@link crit},
   * {@link alert} and {@link emerg}
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $message The message to log.
   * @static
   * @access public
   */
  public static function err( $message ) { self::self()->send( $message, PEAR_LOG_ERR ); }

  /**
   * Logging method
   * 
   * Use this type of log for warnings.  Something that could be an error, but may not be.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $message The message to log.
   * @static
   * @access public
   */
  public static function warning( $message ) { self::self()->send( $message, PEAR_LOG_WARNING ); }

  /**
   * Logging method
   * 
   * Use this type of log to make note of complicated procedures.  Similar to {@link debug} but
   * these should remain in the code after implementation is finished.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $message The message to log.
   * @static
   * @access public
   */
  public static function notice( $message ) { self::self()->send( $message, PEAR_LOG_NOTICE ); }

  /**
   * Logging method
   * 
   * Use this type of log to help debug a procedure.  After implementation is finished they should
   * be removed from the code.  For complicated procedures where it is helpful to keep debug logs
   * use {@link notice} instead.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $message The message to log.
   * @static
   * @access public
   */
  public static function debug( $message ) { self::self()->send( $message, PEAR_LOG_DEBUG ); }
  
  /**
   * Logging method
   * 
   * This is a special convenience method that sends the results of a print_r call on the provided
   * variable as a debug log.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param mixed $variable The variable to expand.
   * @param string $label The variable's label (leave false for no label)
   * @static
   * @access public
   */
  public static function print_r( $variable, $label = false )
  {
    $message = !is_bool( $variable )
             ? print_r( $variable, true )
             : ( $variable ? 'true' : 'false' ); // print_r doesn't display booleans
    self::debug( 'print_r'.( $label ? "($label)" : '' ).": $message" );
  }
  
  /**
   * Logging method
   * 
   * This type of log is special.  It is used to track activity performed by the application so
   * it can be audited at a later date.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $message The message to log.
   * @static
   * @access public
   */
  public static function info( $message ) { self::self()->send( $message, PEAR_LOG_INFO ); }

  /**
   * Returns the backtrace as a log-friendly string.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return string
   * @access private
   */
  private function backtrace()
  {
    $backtrace = "";
    foreach( debug_backtrace( false ) as $index => $trace )
    {
      if( 0 == $index ) continue; // first trace is this function
      if( 1 == $index ) continue; // second trace is the log function
      if( 2 == $index ) continue; // second trace is the public log function
      $backtrace .= '  ['.( $index - 2 ).'] '.
                    ( isset( $trace['class'] ) ? $trace['class'].'::' : '' ).
                    $trace[ 'function' ].'()'."\n";
    }
    return $backtrace;
  }

  /**
   * Master logging function.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $message The message to log.
   * @param int $type The PEAR Log type (PEAR_LOG_ERR, PEAR_LOG_WARNING, etc)
   * @access private
   */
  private function send( $message, $type )
  {
    // make sure we have a session
    if( !class_exists( 'sabretooth\business\session' ) || !business\session::exists() ) return;

    // if in devel mode log everything to firephp
    if( util::in_devel_mode() )
    {
      $type_string = self::log_level_to_string( $type );
      $firephp = \FirePHP::getInstance( true );
      if( PEAR_LOG_INFO == $type ||
          PEAR_LOG_NOTICE == $type ||
          PEAR_LOG_DEBUG == $type )
      {
        $firephp->info( $message, $type_string );
      }
      else if( PEAR_LOG_EMERG == $type ||
               PEAR_LOG_ALERT == $type ||
               PEAR_LOG_CRIT == $type ||
               PEAR_LOG_ERR == $type )
      {
        $firephp->error( $message, $type_string );
      }
      else // PEAR_LOG_WARNING
      {
        $firephp->warn( $message." backtrace: ".$this->backtrace(), $type_string );
      }
    }
    else // we are in production mode
    {
      if( PEAR_LOG_EMERG == $type ||
          PEAR_LOG_ALERT == $type ||
          PEAR_LOG_CRIT == $type ||
          PEAR_LOG_ERR == $type ||
          PEAR_LOG_INFO == $type )
      {
        // log major stuff to an error log
        $this->initialize_logger( 'file' );
        $this->loggers[ 'file' ]->log( $this->backtrace()."\n".$message, $type );
      }
      else // PEAR_LOG_WARNING, PEAR_LOG_NOTICE, PEAR_LOG_DEBUG
      {
        // ignore warnings, notices and debug logs
      }
    }
  }

  /**
   * Initialize loggers if and when they are needed.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $type The type of log ('err', 'warning', etc)
   * @throws exception\runtime
   * @access private
   */
  private function initialize_logger( $type )
  {
    if( 'display' == $type )
    {
      if( NULL == $this->loggers[ 'display' ] )
      {
        // display means html, so let's pretty up the output a bit
        $conf = array(
          'lineFormat' => '<font color=red>%3$s in</font> '.
                          '<font color=blue>%8$s::%7$s</font> '.
                          '<font color=red>(%6$s):</font>'."\n".
                          '%4$s',
          'timeFormat' => '%H:%M:%S',
          'error_prepend' => '<pre style="font-weight: bold; color: #B0B0B0; background: black">',
          'error_append' => '</pre>',
          'linebreak' => '',
          'rawText' => true );
        $this->loggers[ 'display' ] = \Log::singleton( 'display', '', '', $conf );
      }
    }
    else if( 'file' == $type )
    {
      if( NULL == $this->loggers[ 'file' ] )
      {
        $conf = array(
          'append' => true,
          'locking' => true,
          'timeFormat' => '%Y-%m-%d (%a) %H:%M:%S' );
        $this->loggers[ 'file' ] = \Log::singleton( 'file', LOG_FILE_PATH, '', $conf );
      }
    }
    else if( 'firebug' == $type )
    {
      if( NULL == $this->loggers[ 'firebug' ] )
      {
        $conf = array(
          'lineFormat' => '%3$s in %8$s::%7$s (%6$s): %4$s',
          'timeFormat' => '%H:%M:%S' );
        $this->loggers[ 'firebug' ] = \Log::singleton( 'firebug', '', '', $conf );
      }
    }
    else
    {
      throw new exception\runtime(
        'Unable to create invalid logger type "'.$type.'"', __METHOD__ );
    }
  }

  /**
   * Returns a string representation of a pear log level constant
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param int $constant a PEAR_LOG_* constant
   * @static
   * @access private
   */
  static private function log_level_to_string( $constant )
  {
    $string = '';

    if( PEAR_LOG_EMERG == $constant ) $string = 'emergency';
    else if( PEAR_LOG_ALERT == $constant ) $string = 'alert';
    else if( PEAR_LOG_CRIT == $constant ) $string = 'critical';
    else if( PEAR_LOG_ERR == $constant ) $string = 'error';
    else if( PEAR_LOG_WARNING == $constant ) $string = 'warning';
    else if( PEAR_LOG_NOTICE == $constant ) $string = 'notice';
    else if( PEAR_LOG_INFO == $constant ) $string = 'info';
    else if( PEAR_LOG_DEBUG == $constant ) $string = 'debug';
    else $string = 'unknown';

    return $string;
  }

  /**
   * A error handling function that uses the log class as the error handler
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @throws exception\base_exception
   * @ignore
   */
  static public function error_handler( $level, $message, $file, $line )
  {
    // ignore ldap errors
    if( 0 < preg_match( '/^ldap_[a-z_0-9]()/', $message ) ) return;

    $message .= "\n in $file on line $line (errno: $level)";
    if( E_PARSE == $level ||
        E_COMPILE_ERROR == $level ||
        E_USER_ERROR == $level ||
        E_CORE_ERROR == $level ||
        E_ERROR == $level )
    {
      log::emerg( $message );
      // When this function is called due to a fatal error it will die afterwards so we cannot
      // throw an exception.  Instead we can build the exception and emulate what is done in
      // the index/widget/action scripts.
      // just as is done in the widget and action scripts.
      $e = new exception\base_exception( $message, $level );
      $result_array = array(
        'error_type' => ucfirst( $e->get_type() ),
        'error_code' => $e->get_code(),
        'error_message' => '' );

      if( util::in_action_mode() || util::in_widget_mode() )
      { // send the error in json format in an http error header
        util::send_http_error( json_encode( $result_array ) );
      }
      else
      { // output the error using the basic php template
        include TPL_PATH.'/index_error.php';
      }
      exit;
    }
    else if( E_COMPILE_WARNING == $level ||
             E_CORE_WARNING == $level ||
             E_WARNING == $level ||
             E_USER_WARNING == $level ||
             E_STRICT == $level ||
             E_RECOVERABLE_ERROR == $level )
    {
      log::err( $message );
    }
    else if( E_NOTICE == $level ||
             E_USER_NOTICE == $level ||
             E_DEPRECATED == $level ||
             E_USER_DEPRECATED == $level )
    {
      log::warning( $message );
    }
    
    // from PHP docs:
    //   It is important to remember that the standard PHP error handler is completely bypassed for
    //   the error types specified by error_types unless the callback function returns FALSE.
    return false;
  }

  /**
   * A error handling function that uses the log class as the error handler
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @ignore
   */
  static public function fatal_error_handler()
  {
    $error = error_get_last();

    if( $error )
    {
      log::error_handler( $error['type'], $error['message'], $error['file'], $error['line'] );
    }
  }

  /**
   * An array containing all the PEAR Log objects used by the class.
   * @var array( Log )
   * @access private
   */
  private $loggers;
}

// define a custom error handlers
set_error_handler( array( '\sabretooth\log', 'error_handler' ) );
register_shutdown_function( array( '\sabretooth\log', 'fatal_error_handler' ) );
?>
