<?php
/**
 * push.php
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
  
  // Try creating an operation and calling the push provided by the POST variables
  if( !isset( $_POST['subject'] ) ) throw new exception\argument( 'subject', NULL, 'PUSH__SCRIPT' );
  if( !isset( $_POST['name'] ) ) throw new exception\argument( 'name', NULL, 'PUSH__SCRIPT' );

  $push_name = $_POST['subject'].'_'.$_POST['name'];
  $push_class = 'sabretooth\\ui\\push\\'.$push_name;
  $push_args = isset( $_POST ) ? $_POST : NULL;

  // create the operation using the provided args then execute it
  $operation = new $push_class( $push_args );
  if( !is_subclass_of( $operation, 'sabretooth\\ui\\push' ) )
    throw new exception\runtime(
      'Invoked operation "'.$push_class.'" is invalid.', 'PUSH__SCRIPT' );
  
  $operation->finish();
  business\session::self()->log_activity( $operation, $push_args );
  log::notice(
    sprintf( 'finished script: executed push "%s", processing time %0.2f seconds',
             $push_class,
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
