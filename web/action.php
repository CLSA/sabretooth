<?php
/**
 * action.php
 * 
 * Web script which can be called to perform operations on the system.
 * This script should only ever be called by an AJAX POST request.
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */
namespace sabretooth;
ob_start();
 
// the array to return, encoded as JSON
$result_array = array( 'success' => true );

try
{
  // load web-script common code
  require_once 'sabretooth.inc.php';
  
  // if in devel mode allow command line arguments in place of POST variables
  if( util::in_devel_mode() && defined( 'STDIN' ) )
  {
    $_POST['operation'] = isset( $argv[1] ) ? $argv[1] : NULL;
    $_POST['action'] = isset( $argv[2] ) ? $argv[2] : NULL;
  }

  // Try creating an operation and calling the action provided by the post.
  if( !isset( $_POST['operation'] ) || !isset( $_POST['action'] ) )
    throw new exception\runtime( 'invalid script variables' );

  $operation_name = $_POST['operation'];
  $action_name = $_POST['action'];
  $args = array();
  if( isset( $_POST['args'] ) ) parse_str( $_POST['args'], $args );

  // create the operation (and verify that it is an operation)
  $class_name = 'sabretooth\\business\\'.$operation_name;
  $operation = new $class_name();
  if( !is_subclass_of( $operation, 'sabretooth\\business\\operation' ) )
    throw new exception\runtime( "invalid operation '$operation'" );

  // set the action and make sure that it is valid
  if( !method_exists( $operation, $action_name ) )
    throw new exception\runtime( "invalid action '$action'" );

  // execute the action (may throw a permission error)
  log::notice( "executing action: $operation_name::$action_name" );
  call_user_func_array( array( $operation, $action_name ), $args );
}
catch( exception\database $e )
{
  log::err( "Database exception ".$e );
  $result_array['success'] = false;
  $result_array['error'] = 'database';
}
catch( exception\missing $e )
{
  log::err( "Missing exception ".$e );
  $result_array['success'] = false;
  $result_array['error'] = 'missing';
}
catch( exception\permission $e )
{
  log::err( "Permission exception ".$e );
  $result_array['success'] = false;
  $result_array['error'] = 'permission';
}
catch( exception\runtime $e )
{
  log::err( "Runtime exception ".$e );
  $result_array['success'] = false;
  $result_array['error'] = 'runtime';
}
catch( \Exception $e )
{
  log::err( "Last minute ".$e );
  $result_array['success'] = false;
  $result_array['error'] = 'unknown';
}

// flush any output
ob_end_clean();

// flush any output
ob_end_clean();

if( true == $result_array['success'] )
{
  print json_encode( $result_array );
}
else
{
  \HttpResponse::status( 400 );
  \HttpResponse::setContentType('application/json');
  \HttpResponse::setData( json_encode( $result_array ) );
  \HttpResponse::send();
}
?>
