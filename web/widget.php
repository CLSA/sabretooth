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
  require_once 'Twig/Autoloader.php';
  \Twig_Autoloader::register();
  
  // set up the template engine
  $loader = new \Twig_Loader_Filesystem( TPL_PATH );
  $twig = new \Twig_Environment( $loader, array( 'debug' => util::in_devel_mode(),
                                                 'strict_variables' => util::in_devel_mode() ) );
  $twig->addFilter( 'count', new \Twig_Filter_Function( 'count' ) );
  
  // determine which widget to render based on the GET variables
  if( !isset( $_GET['slot'] ) || !is_string( $_GET['slot'] ) )
    throw new exception\runtime( 'invalid script variables' );
  $slot_name = isset( $_GET['slot'] ) ? $_GET['slot'] : NULL;
  $widget['name'] = isset( $_GET['widget'] ) ? $_GET['widget'] : NULL;
  $widget['args'] = isset( $_GET ) ? $_GET : NULL;
  $go_prev = isset( $_GET['prev'] ) && 1 == $_GET['prev'];
  $go_next = isset( $_GET['next'] ) && 1 == $_GET['next'];
  $refresh = isset( $_GET['refresh'] ) && 1 == $_GET['refresh'];
  
  if( $go_prev )
  {
    $widget = session::self()->slot_prev( $slot_name );
  }
  else if( $go_next )
  {
    $widget = session::self()->slot_next( $slot_name );
  }
  else if( $refresh )
  {
    $current_widget = session::self()->slot_current( $slot_name );
    if( !is_null( $current_widget ) ) $widget = $current_widget;
  }

  if( is_null( $widget['name'] ) )
    throw new exception\runtime( 'invalid script variables' );
  
  $widget_class = '\\sabretooth\\ui\\'.$widget['name'];
  
  // create the widget using the provided args then finish it
  $operation = new $widget_class( $widget['args'] );
  if( !is_subclass_of( $operation, 'sabretooth\\ui\\widget' ) )
    throw new exception\runtime( "invalid widget '$widget_class'" );

  $operation->finish();
  $twig_template = $twig->loadTemplate( $widget['name'].'.twig' );
  
  // render the widget and report to the session
  log::notice( 'rendering widget: '.$widget['name'].' in slot '.$slot_name );
  $result_array['output'] = $twig_template->render( $operation->get_variables() );

  // don't push or log prev/next/refresh requests
  if( !( $go_prev || $go_next || $refresh ) )
  {
    session::self()->slot_push( $slot_name, $widget['name'], $widget['args'] );
    session::self()->log_activity( $operation, $_SERVER['QUERY_STRING'] );
  }
}
catch( exception\base_exception $e )
{
  $type = $e->get_type();
  log::err( ucwords( $type )." ".$e );
  $result_array['success'] = false;
  $result_array['error'] = $type;
}
catch( \Twig_Error $e )
{
  log::err( "Template ".$e );
  $result_array['success'] = false;
  $result_array['error'] = 'template';
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
  print $result_array['output'];
}
else
{
  \HttpResponse::status( 400 );
  \HttpResponse::setContentType('application/json');
  \HttpResponse::setData( json_encode( $result_array ) );
  \HttpResponse::send();
}
?>
