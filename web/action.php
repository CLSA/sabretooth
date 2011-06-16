<?php
/**
 * action.php
 * 
 * Web script which can be called to perform operations on the system.
 * This script should only ever be called by an AJAX POST request.
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @throws exception\argument, exception\runtime
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
  if( !isset( $_POST['subject'] ) ) throw new exception\argument( 'subject', NULL, 'ACTION__SCRIPT' );
  if( !isset( $_POST['name'] ) ) throw new exception\argument( 'name', NULL, 'ACTION__SCRIPT' );

  $action_name = $_POST['subject'].'_'.$_POST['name'];
  $action_class = 'sabretooth\\ui\\action\\'.$action_name;
  $action_args = isset( $_POST ) ? $_POST : NULL;

  // create the operation using the provided args then execute it
  $operation = new $action_class( $action_args );
  if( !is_subclass_of( $operation, 'sabretooth\\ui\\action' ) )
    throw new exception\runtime(
      'Invoked operation "'.$action_class.'" is invalid.', 'ACTION__SCRIPT' );
  
  $operation->execute();
  business\session::self()->log_activity( $operation, $action_args );
  log::notice(
    sprintf( 'finished script: executed action "%s", processing time %0.2f seconds',
             $action_class,
             util::get_elapsed_time() ) );
}
catch( exception\base_exception $e )
{
  $type = $e->get_type();
  $result_array['success'] = false;
  $result_array['error_type'] = ucfirst( $type );
  $result_array['error_code'] = $e->get_code();
  $result_array['error_message'] = 'notice' == $type ? $e->get_notice() : '';

  if( 'notice' != $type ) log::err( ucwords( $type )." ".$e );
}
catch( \Exception $e )
{
  $code = util::convert_number_to_code( SYSTEM_BASE_ERROR_NUMBER );
  $result_array['success'] = false;
  $result_array['error_type'] = 'System';
  $result_array['error_code'] = $code;
  $result_array['error_message'] = '';

  if( class_exists( 'sabretooth\log' ) ) log::err( "Last minute ".$e );
}

// flush any output
ob_end_clean();

if( true == $result_array['success'] )
{
  print json_encode( $result_array );
}
else
{
  util::send_http_error( json_encode( $result_array ) );
}
?>
