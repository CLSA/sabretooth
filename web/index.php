<?php
/**
 * index.php
 * 
 * Main web script which drives the application.
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */
namespace sabretooth;

// hack for logging out HTTP authentication
if( isset( $_POST ) && isset( $_POST['logout'] ) && 'logout' == $_POST['logout'] )
{
  $method = 'http'.( 'on' == $_SERVER['HTTPS'] ? 's' : '' );
  header( 'Location: '.$method.'://none:none@'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'] );
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
  
  $widget = new ui\main();
  $widget->finish();
  $twig_template = $twig->loadTemplate( 'main.html' );

  $output = $twig_template->render( ui\widget::get_variables() );
  print $output;
}
// TODO: need to handle exceptions properly when in development mode using error dialogs
catch( exception\database $e )
{
  log::err( "Database exception ".$e );
}
catch( exception\missing $e )
{
  log::err( "Missing exception ".$e );
}
catch( exception\permission $e )
{
  log::err( "Permission exception ".$e );
}
catch( \Twig_Error_Runtime $e )
{
  log::err( "Template ".$e );
}
catch( \Exception $e )
{
  log::err( "Last minute ".$e );
}
?>
