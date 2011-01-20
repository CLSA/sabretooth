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

// used to communicate with the GUI
$result_array = array( 'error' => false );

try
{
  // register autoloaders
  autoloader::register();
  
  // set up the session
  $session = session::singleton( $SETTINGS );
  $session->initialize();
  
  // Create an operation manager and try executing the operation
  // Operations are sent as strings in the form: operation.action
  $op_array = explode( '.', $_POST[ 'operation' ] );

  if( 2 != count( $op_array ) )
  {
    $result_array['error'] = 'invalid1';
  }
  else
  {
    // create the operation (and verify that it is an operation)
    $class_name = 'sabretooth\\business\\'.$op_array[0];
    $operation = new $class_name();
    if( !is_subclass_of( $operation, 'sabretooth\\business\\operation' ) )
    {
      $result_array['error'] = 'invalid2';
    }
    else
    {
      // set the action and make sure that it is valid
      if( !$operation->set_action( $op_array[1] ) )
      {
        $result_array['error'] = 'invalid3';
      }
      else
      {
        // execute the action (may throw a permission error)
        //$operation->execute();
        $result_array['success'] = true;
      }
    }
  }
}
catch( exception\missing $e )
{
  $result_array['error'] = 'invalid4';
}
catch( exception\permission $e )
{
  $result_array['error'] = 'permission';
}
catch( \Exception $e )
{
  $result_array['error'] = 'unknown';
}

// output the result in JSON format
print json_encode( $result_array );
?>
