<?php
/**
 * widget.php
 * 
 * Web script which loads widgets.
 * This script should only ever be called by an AJAX GET request.
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */
namespace sabretooth;
ob_start();

// the array to return, encoded as JSON if there is an error
$result_array = array( 'success' => true );

try
{
  // load web-script common code
  require_once 'sabretooth.inc.php';

  // register autoloaders
  include_file( TWIG_PATH.'/lib/Twig/Autoloader.php' );
  \Twig_Autoloader::register();
  
  // if in devel mode allow command line arguments in place of GET variables
  if( util::in_devel_mode() && defined( 'STDIN' ) )
  {
    $_GET['widget'] = isset( $argv[1] ) ? $argv[1] : NULL;
  }

  // set up the template engine
  $loader = new \Twig_Loader_Filesystem( TPL_PATH );
  $twig = new \Twig_Environment( $loader, array( 'debug' => util::in_devel_mode(),
                                                 'strict_variables' => util::in_devel_mode() ) );
  $twig->addFilter( 'count', new \Twig_Filter_Function( 'count' ) );
  foreach( $SETTINGS[ 'paths' ] as $path_name => $path_value )
    $twig->addGlobal( $path_name, $path_value );
  
  // create and setup the called widget
  $widget_name = isset( $_GET['widget'] ) ? $_GET['widget'] : NULL;
  if( is_null( $widget_name ) )
    throw new exception\runtime( 'invalid script variables' );
  
  $widget_class = '\\sabretooth\\ui\\'.$widget_name;
  
  // determine the widget arguments
  if( util::in_devel_mode() && defined( 'STDIN' ) && 2 < $argc ) $widget_args = $argv;
  else $widget_args = isset( $_GET ) ? $_GET : NULL;

  // autoloader doesn't work on dynamic class names for PHP 5.3.2
  include_file( API_PATH.'/ui/'.$widget_name.'.class.php' );
  $widget = new $widget_class( $widget_args );
  $widget->finish();
  $twig_template = $twig->loadTemplate( $widget_name.'.html' );

  $result_array['output'] = $twig_template->render( ui\widget::get_variables() );
}
// TODO: need to handle exceptions properly when in development mode using error dialogs
catch( exception\database $e )
{
  log::err( "Database exception ".$e->get_message() );
  $result_array['success'] = false;
  $result_array['error'] = 'database';
}
catch( exception\missing $e )
{
  log::err( "Missing exception ".$e->get_message() );
  $result_array['success'] = false;
  $result_array['error'] = 'database';
}
catch( exception\permission $e )
{
  log::err( "Permission exception ".$e->get_message() );
  $result_array['success'] = false;
  $result_array['error'] = 'database';
}
catch( \Twig_Error_Runtime $e )
{
  log::err( "Template ".$e->__toString() );
  $result_array['success'] = false;
  $result_array['error'] = 'database';
}
catch( \Exception $e )
{
  log::err( "Last minute ".$e->__toString() );
  $result_array['success'] = false;
  $result_array['error'] = 'database';
}

// flush any output
ob_end_clean();

if( true == $result_array['success'] )
{
  print $result_array['output'];
}
else
{
  header( json_encode( $result_array ), true, 400 );
//  \HttpResponse::status( 400 );
//  \HttpResponse::setContentType('application/json');
//  \HttpResponse::setData( json_encode( $result_array ) );
//  \HttpResponse::send();
}
?>
