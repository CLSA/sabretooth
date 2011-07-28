<?php
/**
 * pull.php
 * 
 * Web script which can be called to retrieve data from the system.
 * This script provides a GET based web service for reading.
 * HTTP accept headers are not yet implemented.
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
  $pull_url = str_replace( $base_url_path, '', $_SERVER['REDIRECT_URL'] );
  $pull_tokens = explode( '/', $pull_url );

  // There should be at least two parts to the pull redirect url
  if( 2 > count( $pull_tokens ) )
    throw new exception\runtime( 'Invalid pull URL "'.$pull_url.'".', 'PULL__SCRIPT' );

  $pull_name = $pull_tokens[0].'_'.$pull_tokens[1];
  $pull_class = 'sabretooth\\ui\\pull\\'.$pull_name;
  $pull_args = isset( $_GET ) ? $_GET : NULL;

  // create the operation using the url and GET variables then execute it
  $operation = new $pull_class( $pull_args );
  if( !is_subclass_of( $operation, 'sabretooth\\ui\\pull' ) )
    throw new exception\runtime(
      'Invoked operation "'.$pull_class.'" is invalid.', 'PULL__SCRIPT' );
  
  $data_type = $operation->get_data_type();
  $data = $operation->finish();
  business\session::self()->log_activity( $operation, $pull_args );
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
  log::err( "Last minute ".$e );
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
  if( 'json' == $data_type )
  {
    $result_array['data'] = $data;
    print json_encode( $result_array );
  }
  else
  {
    header( 'Pragma: public');
    header( 'Expires: 0');
    header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
    header( 'Cache-Control: private', false );
    header( 'Content-Type: application/force-download' );
    header( 'Content-Type: application/octet-stream' );
    header( 'Content-Type: application/ms-excel' );
    header( 'Content-Disposition: attachment; filename='.$pull_name.'.'.$data_type );
    header( 'Content-Transfer-Encoding: binary ' );
    header( 'Content-Length: '.strlen( $data ) );
    print $data;
  }
}
else
{
  // make sure to fail any active transaction
  if( class_exists( 'business\session' ) &&
      business\session::exists() &&
      business\session::self()->is_initialized() )
    business\session::self()->get_database()->fail_transaction();

  if( !isset( $data_type ) || 'json' == $data_type )
    util::send_http_error( json_encode( $result_array ) );
  else include 'error.php';
}
?>
