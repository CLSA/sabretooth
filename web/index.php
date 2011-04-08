<?php
/**
 * index.php
 * 
 * Main web script which drives the application.
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */
namespace sabretooth;

// the array to return, encoded as JSON if there is an error
$result_array = array( 'success' => true );

// hack for logging out HTTP authentication
if( isset( $_POST ) && isset( $_POST['logout'] ) && 'logout' == $_POST['logout'] )
{
  // force the user to log out by sending a header with invalid HTTP auth credentials
  $method = 'http'.( 'on' == $_SERVER['HTTPS'] ? 's' : '' );
  header( 'Location: '.$method.'://none:none@'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'] );
  exit;
}

try
{
  // load web-script common code
  require_once 'sabretooth.inc.php';

  // setup Twig
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
  
  $twig_template = $twig->loadTemplate( 'main.twig' );
  
  // Since there is no main widget we need set up the template variables here
  $version = $session->get_setting( 'version', 'JQUERY_UI' );
  $variables = array( 'jquery_ui_css_path' => '/'.$theme.'/jquery-ui-'.$version.'.custom.css',
                      'survey_url' => $session->get_survey_url() );
  
  $result_array['output'] = $twig_template->render( $variables );
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
  $code = class_exists( 'sabretooth\util' )
        ? util::convert_number_to_code( SYSTEM_BASE_ERROR_NUMBER )
        : 0;
  if( class_exists( 'sabretooth\log' ) ) log::err( "Last minute ".$e );
  $result_array['success'] = false;
  $result_array['error_type'] = 'System';
  $result_array['error_code'] = $code;
  $result_array['error_message'] = '';
}

if( true == $result_array['success'] )
{
  print $result_array['output'];
}
else
{
  // Since the error may have been caused by the template engine, output using a php template
  include TPL_PATH.'/index_error.php';
}
?>
