<?php
/**
 * widget.php
 * 
 * Web script which loads widgets.
 * This script should only ever be called by an AJAX GET request.
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @throws exception\argument, exception\runtime
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
  
  $session = business\session::self();
  $theme = $session->get_theme();

  // set up the template engine
  $loader = new \Twig_Loader_Filesystem( TPL_PATH );
  $twig = new \Twig_Environment( $loader, array( 'debug' => util::in_devel_mode(),
                                                 'strict_variables' => util::in_devel_mode(),
                                                 'cache' => TEMPLATE_CACHE_PATH ) );
  $twig->addFilter( 'count', new \Twig_Filter_Function( 'count' ) );
  $twig->addGlobal( 'SIP_ENABLED', \sabretooth\business\voip_manager::self()->get_sip_enabled() );
  $twig->addGlobal( 'FOREGROUND_COLOR', util::get_foreground_color( $theme ) );
  $twig->addGlobal( 'BACKGROUND_COLOR', util::get_background_color( $theme ) );
  
  // determine which widget to render based on the GET variables
  $session = business\session::self();
  if( !isset( $_GET['slot'] ) || !is_string( $_GET['slot'] ) )
    throw new exception\argument( 'slot', NULL, 'WIDGET_SCRIPT' );
  $slot_name = isset( $_GET['slot'] ) ? $_GET['slot'] : NULL;
  $widget['name'] = isset( $_GET['widget'] ) ? $_GET['widget'] : NULL;
  $current_widget = $session->slot_current( $slot_name );

  // if we are loading the same widget as last time then merge the arguments
  $widget['args'] = $_GET;
  if( !is_null( $current_widget ) &&
      is_array( $current_widget['args'] ) &&
      $widget['name'] == $current_widget['name'] )
  {
    // A simple array_merge call will not work since we may have a multi-dimensional array
    // so we have to go through each argument, add them if it isn't an array and merge it
    // if it is
    foreach( $current_widget['args'] as $key => $arg )
    {
      $widget['args'][$key] = is_array( $arg ) && array_key_exists( $key, $widget['args'] )
                            ? array_merge( $arg, $widget['args'][$key] )
                            : $arg;
    }
  }

  $go_prev = isset( $_GET['prev'] ) && 1 == $_GET['prev'];
  $go_next = isset( $_GET['next'] ) && 1 == $_GET['next'];
  $refresh = isset( $_GET['refresh'] ) && 1 == $_GET['refresh'];
    
  // if the prev, next or refresh buttons were invoked, adjust the widget appropriately
  if( $go_prev )
  {
    $widget = $session->slot_prev( $slot_name );
  }
  else if( $go_next )
  {
    $widget = $session->slot_next( $slot_name );
  }
  else if( $refresh )
  {
    if( !is_null( $current_widget ) ) $widget = $current_widget;
  }

  if( is_null( $widget['name'] ) )
    throw new exception\runtime( 'Unable to determine widget name.', 'WIDGET_SCRIPT' );
  
  $widget_class = '\\sabretooth\\ui\\'.$widget['name'];
  
  // create the widget using the provided args then finish it
  $operation = new $widget_class( $widget['args'] );
  if( !is_subclass_of( $operation, 'sabretooth\\ui\\widget' ) )
    throw new exception\runtime(
      'Invoked widget "'.$widget_class.'" is invalid.', 'WIDGET_SCRIPT' );

  $operation->finish();
  $twig_template = $twig->loadTemplate( $widget['name'].'.twig' );
  
  // render the widget and report to the session
  $result_array['output'] = $twig_template->render( $operation->get_variables() );

  // don't push or log prev/next/refresh requests
  if( !( $go_prev || $go_next || $refresh ) )
  {
    $session->slot_push( $slot_name, $widget['name'], $widget['args'] );
    $session->log_activity( $operation, $widget['args'] );
  }

  log::notice(
    sprintf( 'finished script: rendered "%s" in slot "%s", processing time %0.2f seconds',
             $widget['name'],
             $slot_name,
             util::get_elapsed_time() ) );
}
catch( exception\base_exception $e )
{
  $type = $e->get_type();
  log::err( ucwords( $type )." ".$e );
  $result_array['success'] = false;
  $result_array['error_type'] = ucfirst( $type );
  $result_array['error_code'] = $e->get_code();
  $result_array['error_message'] = 'notice' == $type ? $e->get_notice() : '';
}
catch( \Twig_Error $e )
{
  $class_name = get_class( $e );
  if( 'Twig_Error_Syntax' == $class_name ) $code = 1;
  else if( 'Twig_Error_Runtime' == $class_name ) $code = 2;
  else if( 'Twig_Error_Loader' == $class_name ) $code = 3;
  else $code = 0;
  
  $code = util::convert_number_to_code( TEMPLATE_BASE_ERROR_NUMBER + $code );
  log::err( "Template ".$e );
  $result_array['success'] = false;
  $result_array['error_type'] = 'Template';
  $result_array['error_code'] = $code;
  $result_array['error_message'] = '';
}
catch( \Exception $e )
{
  $code = util::convert_number_to_code( SYSTEM_BASE_ERROR_NUMBER );
  log::err( "Last minute ".$e );
  $result_array['success'] = false;
  $result_array['error_type'] = 'System';
  $result_array['error_code'] = $code;
  $result_array['error_message'] = '';
}

// flush any output
ob_end_clean();

if( true == $result_array['success'] )
{
  print $result_array['output'];
}
else
{
  util::send_http_error( json_encode( $result_array ) );
}
?>
