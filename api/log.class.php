<?php
/**
 * log.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth
 */

namespace sabretooth;

require_once API_PATH.'/singleton.class.php';
require_once API_PATH.'/util.class.php';
require_once API_PATH.'/database/log.class.php';

// PEAR
require_once 'Log.php';

/**
 * log: handles all logging
 *
 * The log class is used to log to various outputs depending on the application's running mode.
 * There are several logging functions, each of which have their purpose.  Use this class as
 * follows:
 * <code>
 * log::singleton()->err( "There is an error here." );
 * log::singleton()->emerg( "The server is on fire!!" );
 * </code>
 * @package sabretooth
 */
final class log extends singleton
{
  /**
   * Constructor.
   * 
   * Since this class uses the singleton pattern the constructor is never called directly.  Instead
   * use the {@link singleton} method.
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
   * @access public
   */
  public function emerg( $message ) { $this->log( $message, PEAR_LOG_EMERG ); }

  /**
   * Logging method
   * 
   * This is the second highest severity log.  It should be used to describe a major problem which
   * needs to be brought to administrators' attention in the near future (ie: use it sparingly).
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $message The message to log.
   * @access public
   */
  public function alert( $message ) { $this->log( $message, PEAR_LOG_ALERT ); }

  /**
   * Logging method
   * 
   * Use this type of log when there is a problem that is more severe than a usual error, but not
   * severe enough to notify administrators.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $message The message to log.
   * @access public
   */
  public function crit( $message ) { $this->log( $message, PEAR_LOG_CRIT ); }

  /**
   * Logging method
   * 
   * Use this type of log when there is an error.  For very severe errors see {@link crit},
   * {@link alert} and {@link emerg}
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $message The message to log.
   * @access public
   */
  public function err( $message ) { $this->log( $message, PEAR_LOG_ERR ); }

  /**
   * Logging method
   * 
   * Use this type of log for warnings.  Something that could be an error, but may not be.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $message The message to log.
   * @access public
   */
  public function warning( $message ) { $this->log( $message, PEAR_LOG_WARNING ); }

  /**
   * Logging method
   * 
   * Use this type of log to make note of complicated procedures.  Similar to {@link debug} but
   * these should remain in the code after implementation is finished.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $message The message to log.
   * @access public
   */
  public function notice( $message ) { $this->log( $message, PEAR_LOG_NOTICE ); }

  /**
   * Logging method
   * 
   * Use this type of log to help debug a procedure.  After implementation is finished they should
   * be removed from the code.  For complicated procedures where it is helpful to keep debug logs
   * use {@link notice} instead.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $message The message to log.
   * @access public
   */
  public function debug( $message ) { $this->log( $message, PEAR_LOG_DEBUG ); }
  
  /**
   * Logging method
   * 
   * This type of log is special.  It is used to track activity performed by the application so
   * it can be audited at a later date.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $message The message to log.
   * @access public
   */
  public function info( $message ) { $this->log( $message, PEAR_LOG_INFO ); }

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
      $backtrace .= '  ['.( $index - 2 ).'] '.$trace[ 'class' ].'::'.$trace[ 'function' ].'()'."\n";
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
  private function log( $message, $type )
  {
    // handle logs differently when we are in developer mode
    if( util::devel_mode() )
    {
      if( PEAR_LOG_EMERG == $type ||
          PEAR_LOG_ALERT == $type ||
          PEAR_LOG_CRIT == $type ||
          PEAR_LOG_ERR == $type )
      {
        // display means html, so pretty up the message
        $message = '<font color=magenta>"'.$message.'"</font>';
        // log major stuff to display
        $this->initialize_logger( 'display' );
        $this->loggers[ 'display' ]->log( $message."\n".$this->backtrace(), $type );
      }
      else if( PEAR_LOG_WARNING == $type ||
               PEAR_LOG_NOTICE == $type ||
               PEAR_LOG_INFO == $type ||
               PEAR_LOG_DEBUG == $type )
      {
        // log minor stuff in firebug
        $this->initialize_logger( 'firebug' );
        $this->loggers[ 'firebug' ]->log( $message."\n".$this->backtrace(), $type );
      }
    }
    else // we are in production mode
    {
      if( PEAR_LOG_EMERG == $type ||
          PEAR_LOG_ALERT == $type ||
          PEAR_LOG_CRIT == $type ||
          PEAR_LOG_ERR == $type )
      {
        // log major stuff to an error log
        $this->initialize_logger( 'file' );
        $this->loggers[ 'file' ]->log( $this->backtrace()."\n".$message, $type );
      }
      else if( PEAR_LOG_WARNING == $type ||
               PEAR_LOG_NOTICE == $type ||
               PEAR_LOG_DEBUG == $type )
      {
        // ignore warnings, notices and debug logs
      }
      else if( PEAR_LOG_INFO == $type )
      {
        // log info to the database (PEAR logger not used)
        $t_log = new database\log();
        $t_log->user_id = session::singleton()->get_user()->get_id();
        $t_log->site_id = session::singleton()->get_site()->get_id();
        $t_log->text = $message;
        $t_log->save();
      }
    }
  }

  /**
   * Initialize loggers if and when they are needed.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $type The type of log ('err', 'warning', etc)
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
        $conf = array();
        $this->loggers[ 'file' ] = \Log::singleton( 'file', '', '', $conf );
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
      assert( false ); // invalid logger
    }
  }

  /**
   * An array containing all the PEAR Log objects used by the class.
   * @var array( Log )
   * @access private
   */
  private $loggers;
}

// use the log class as the error handler
$error_handler = function( $level, $message )
{
  $message .= " (errno: $level)";
  if( E_PARSE == $level ||
      E_COMPILE_ERROR == $level )
  {
    log::singleton()->emerg( $message );
    die( 2 );
  }
  else if( E_USER_ERROR == $level ||
           E_CORE_ERROR == $level ||
           E_ERROR == $level )
  {
    log::singleton()->err( $message );
    die( 1 );
  }
  else if( E_COMPILE_WARNING == $level ||
           E_CORE_WARNING == $level ||
           E_WARNING == $level ||
           E_STRICT == $level )
  {
    log::singleton()->warning( $message );
  }
  else if( E_NOTICE == $level ||
           E_USER_NOTICE == $level ||
           E_DEPRECATED == $level ||
           E_USER_DEPRECATED == $level )
  {
    log::singleton()->notice( $message );
  }
  
  // exception
  else if( E_RECOVERABLE_ERROR == $level )
  {
    throw \Exception( $message );
  }

  return false;
};

set_error_handler( $error_handler );
?>
