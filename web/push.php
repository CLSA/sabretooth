<?php
/**
 * push.php
 * 
 * Web script which can be called to perform operations on the system.
 * This script provides a POST based web service for writing.
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @throws exception\runtime
 */
namespace sabretooth;
ob_start();
 
// the array to return, encoded as JSON
$result_array = array( 'success' => true );

try
{
  // load web-script common code
  require_once 'sabretooth.inc.php';
  
  $base_url_path = substr( $_SERVER['PHP_SELF'], 0, strrpos( $_SERVER['PHP_SELF'], '/' ) + 1 );
  $push_url = str_replace( $base_url_path, '', $_SERVER['REDIRECT_URL'] ); 
  $push_tokens = explode( '/', $push_url );
  
  // There should be at least two parts to the push redirect url
  if( 2 > count( $push_tokens ) )
    throw new exception\runtime( 'Invalid push URL "'.$push_url.'".', 'PULL__SCRIPT' ); 

  $push_name = $push_tokens[0].'_'.$push_tokens[1];
  $push_class = 'sabretooth\\ui\\push\\'.$push_name;
  $push_args = isset( $_POST ) ? $_POST : NULL;
         
  // create the operation using the url and POST variables then execute it
  $operation = new $push_class( $push_args );
  if( !is_subclass_of( $operation, 'sabretooth\\ui\\push' ) )
    throw new exception\runtime(
      'Invoked operation "'.$push_class.'" is invalid.', 'PUSH__SCRIPT' );

  // only use a transaction if the pull requests one
  if( $operation->use_transaction() )
    business\session::self()->get_database()->start_transaction();
  $operation->finish();

  business\session::self()->log_activity( $operation, $push_args );
}
catch( exception\base_exception $e )
{
  $type = $e->get_type();
  $result_array['success'] = false;
  $result_array['error_type'] = ucfirst( $type );
  $result_array['error_code'] = $e->get_code();
  $result_array['error_message'] = $e->get_raw_message();

  // log all but notice and permission exceptions
  if( 'notice' != $type && 'permission' != $type ) log::err( ucwords( $type )." ".$e );
}
catch( \Exception $e )
{
  $code = util::convert_number_to_code( SYSTEM_BASE_ERROR_NUMBER );
  $result_array['success'] = false;
  $result_array['error_type'] = 'System';
  $result_array['error_code'] = $code;
  $result_array['error_message'] = $e->getMessage();

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
  // make sure to fail any active transaction
  if( class_exists( 'sabretooth\business\session' ) &&
      business\session::exists() &&
      business\session::self()->is_initialized() )
    business\session::self()->get_database()->fail_transaction();

  util::send_http_error( json_encode( $result_array ) );
}
?>
