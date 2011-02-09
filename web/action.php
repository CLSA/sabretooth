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
  
  // Try creating an operation and calling the action provided by the post.
  if( !isset( $_POST['subject'] ) || !isset( $_POST['name'] ) )
    throw new exception\runtime( 'invalid script variables' );

  $action_name = $_POST['subject'].'_'.$_POST['name'];
  $action_class = 'sabretooth\\ui\\'.$action_name;
  $action_args = isset( $_POST ) ? $_POST : NULL;

  // create the operation using the provided args then execute it
  $operation = new $action_class( $action_args );
  if( !is_subclass_of( $operation, 'sabretooth\\ui\\action' ) )
    throw new exception\runtime( "invalid operation '$action_class'" );
  
  $operation->execute();
  session::self()->log_activity( $operation, $_SERVER['QUERY_STRING'] );
  log::notice(
    sprintf( 'finished script: executed action "%s", processing time %0.2f seconds',
             $action_class,
             util::get_elapsed_time() ) );
}
catch( exception\base_exception $e )
{
  $type = $e->get_type();
  log::err( ucwords( $type )." ".$e );
  $result_array['success'] = false;
  $result_array['error'] = $type;
}
catch( \Exception $e )
{
  log::err( "Last minute ".$e );
  $result_array['success'] = false;
  $result_array['error'] = 'unknown';
}

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
