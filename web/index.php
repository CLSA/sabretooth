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
  include_file( TWIG_PATH.'/lib/Twig/Autoloader.php' );
  \Twig_Autoloader::register();

  // set up the template engine
  $loader = new \Twig_Loader_Filesystem( TPL_PATH );
  $twig = new \Twig_Environment( $loader, array( 'debug' => util::in_devel_mode(),
                                                 'strict_variables' => util::in_devel_mode() ) );
  $twig->addFilter( 'count', new \Twig_Filter_Function( 'count' ) );
  
  $twig_template = $twig->loadTemplate( 'main.html' );
  
  // Since there is no main widget we need set up the template variables here
  $theme = session::self()->get_theme();
  $version = session::self()->get_setting( 'version', 'JQUERY_UI' );
  $variables = array( 'jquery_ui_css_path' => '/'.$theme.'/jquery-ui-'.$version.'.custom.css',
                      'survey_active' => false ); // TODO: change once survey code is implemented

  $result_array['output'] = $twig_template->render( $variables );
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
  $result_array['error'] = $type;
}
catch( \Exception $e )
{
  log::err( "Last minute ".$e );
  $result_array['success'] = false;
  $result_array['error'] = $type;
}

if( true == $result_array['success'] )
{
  print $result_array['output'];
}
else
{
  // TODO: display a user-friendly error
}
?>
