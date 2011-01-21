<?php
/**
 * action.php
 * 
 * Web script which can be called to perform operations on the system.
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @vertion 0.1
 */
namespace sabretooth;
session_name( 'sabretooth' );
session_start();
define( 'ACTION_MODE', true );

// set up error handling (error_reporting is also called in session's constructor)
ini_set( 'display_errors', '0' );
error_reporting( E_ALL | E_STRICT );

// Function to gracefully handle missing require_once files
function include_file( $file, $no_error = false )
{
  if( !file_exists( $file ) )
  {
    if( !$no_error )
    {
      die( "<pre>\n".
           "FATAL ERROR: Unable to find required file '$file'.\n".
           "Please check that paths in the sabretooth ini are set correctly.\n".
           "</pre>\n" );
    }
  }
  include $file;
}

// load the default, then local settings, then define paths
include_file( 'sabretooth.ini.php' );
include_file( 'sabretooth.local.ini.php', true );
foreach( $SETTINGS[ 'paths' ] as $path_name => $path_value ) define( $path_name, $path_value );

// include necessary files
include_file( API_PATH.'/autoloader.class.php' );

try
{
  // register autoloaders
  autoloader::register();
  
  // set up the session
  $session = session::singleton( $SETTINGS );
  $session->initialize();
  
  // Try creating an operation and calling the action provided by the post.
  if( !isset( $_POST['operation'] ) ||
      !isset( $_POST['action'] ) )
  {
    log::singleton()->err( 'invalid post variables' );
  }
  else
  {
    $operation_name = $_POST['operation'];
    $action_name = $_POST['action'];
    $args = isset( $_POST['args'] ) ? $_POST['args'] : NULL;

    // create the operation (and verify that it is an operation)
    $class_name = 'sabretooth\\business\\'.$operation_name;
    $operation = new $class_name();
    if( !is_subclass_of( $operation, 'sabretooth\\business\\operation' ) )
    {
      log::singleton()->err( "invalid operation '$operation'" );
    }
    else
    {
      // set the action and make sure that it is valid
      if( !method_exists( $operation, $action_name ) )
      {
        log::singleton()->err( "invalid action '$action'" );
      }
      else
      {
        // execute the action (may throw a permission error)
        call_user_func_array( array( $operation, $action_name ), $args );
      }
    }
  }
}
catch( exception\missing $e )
{
  log::singleton()->err( "Missing ".$e->__toString() );
}
catch( exception\permission $e )
{
  log::singleton()->err( "Permission ".$e->__toString() );
}
catch( \Exception $e )
{
  log::singleton()->err( "Last minute ".$e->__toString() );
}

// output the result in JSON format
print json_encode( array( 'success' => true ) );
?>
