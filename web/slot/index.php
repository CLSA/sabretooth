<?php
/**
 * widget.php
 * 
 * Web script which loads widgets.
 * This script provides a GET based web service for user-interface "widgets".
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @throws exception\runtime
 */
namespace sabretooth;
ob_start();

// the array to return, encoded as JSON if there is an error
$result_array = array( 'success' => true );

try
{
  // we need to back up to the main web directory in order for paths to work properly
  chdir( '..' );

  // load web-script common code
  require_once 'sabretooth.inc.php';
  
  $base_url_path = substr( $_SERVER['PHP_SELF'], 0, strrpos( $_SERVER['PHP_SELF'], '/' ) + 1 );
  $widget_url = str_replace( $base_url_path, '', $_SERVER['REDIRECT_URL'] );
  $widget_tokens = explode( '/', $widget_url );

  // There should be at least two parts to the widget redirect url
  if( 2 > count( $widget_tokens ) )
    throw new exception\runtime( 'Invalid widget URL "'.$widget_url.'".', 'PULL__SCRIPT' );

  $slot_name = $widget_tokens[0];
  $slot_action = $widget_tokens[1];
  $widget['name'] = 4 > count( $widget_tokens )
               ? NULL
               : $widget_tokens[2].'_'.$widget_tokens[3];
  $widget['args'] = isset( $_GET ) ? $_GET : NULL;

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
  $twig->addFilter( 'nl2br', new \Twig_Filter_Function( 'nl2br' ) );
  $twig->addGlobal( 'FOREGROUND_COLOR', util::get_foreground_color( $theme ) );
  $twig->addGlobal( 'BACKGROUND_COLOR', util::get_background_color( $theme ) );

  // determine which widget to render based on the GET variables
  $session = business\session::self();
  $current_widget = $session->slot_current( $slot_name );

  // if we are loading the same widget as last time then merge the arguments
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

  // if the prev, next or refresh buttons were invoked, adjust the widget appropriately
  if( 'prev' == $slot_action ) $widget = $session->slot_prev( $slot_name );
  else if( 'next' == $slot_action ) $widget = $session->slot_next( $slot_name );
  else if( 'refresh' == $slot_action && !is_null( $current_widget ) ) $widget = $current_widget;

  if( is_null( $widget['name'] ) )
    throw new exception\runtime( 'Unable to determine widget name.', 'WIDGET__SCRIPT' );
  
  $widget_class = '\\sabretooth\\ui\\widget\\'.$widget['name'];
  
  // create the widget using the provided args then finish it
  $operation = new $widget_class( $widget['args'] );
  if( !is_subclass_of( $operation, 'sabretooth\\ui\\widget' ) )
    throw new exception\runtime(
      'Invoked widget "'.$widget_class.'" is invalid.', 'WIDGET__SCRIPT' );

  $operation->finish();
  $twig_template = $twig->loadTemplate( $widget['name'].'.twig' );
  
  // render the widget and report to the session
  $result_array['output'] = $twig_template->render( $operation->get_variables() );

  // only push and log on load slot actions
  if( 'load' == $slot_action )
  {
    $session->slot_push( $slot_name, $widget['name'], $widget['args'] );
    $session->log_activity( $operation, $widget['args'] );
  }
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
catch( \Twig_Error $e )
{
  $class_name = get_class( $e );
  if( 'Twig_Error_Syntax' == $class_name ) $code = 1;
  else if( 'Twig_Error_Runtime' == $class_name ) $code = 2;
  else if( 'Twig_Error_Loader' == $class_name ) $code = 3;
  else $code = 0;
  
  $code = util::convert_number_to_code( TEMPLATE_BASE_ERROR_NUMBER + $code );
  $result_array['success'] = false;
  $result_array['error_type'] = 'Template';
  $result_array['error_code'] = $code;
  $result_array['error_message'] = '';

  log::err( "Template ".$e );
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
  print $result_array['output'];
}
else
{
  util::send_http_error( json_encode( $result_array ) );
}
?>
