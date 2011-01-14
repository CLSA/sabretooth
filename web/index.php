<?php
/**
 * index.php
 * 
 * Main web script which drives the application.
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @vertion 0.1
 */
namespace sabretooth;

// set up error handling (error_reporting is also called in session's constructor)
ini_set( 'display_errors', '0' );
error_reporting( E_ALL | E_STRICT );

// Function to gracefully handle missing require_once files
function include_file( $file, $no_error = false )
{
  if( !file_exists( $file ) )
  {
    if( !$no_error )
    {
      die( "<pre>\n".
           "FATAL ERROR: Unable to find required file '$file'.\n".
           "Please check that paths in the sabretooth ini are set correctly.\n".
           "</pre>\n" );
    }
  }
  include $file;
}

// load the default, then local settings, then define paths
include_file( 'sabretooth.ini.php' );
include_file( 'sabretooth.local.ini.php', true );
foreach( $SETTINGS[ 'paths' ] as $path_name => $path_value ) define( $path_name, $path_value );

// include necessary files
include_file( API_PATH.'/autoloader.class.php' );
include_file( TWIG_PATH.'/lib/Twig/Autoloader.php' );

try
{
  // register autoloaders
  autoloader::register();
  \Twig_Autoloader::register();

  // set up the session
  $session = session::singleton( $SETTINGS );
  $session->initialize();
  $user = new database\user();

  // set up the template engine
  $loader = new \Twig_Loader_Filesystem( TPL_PATH );
  $twig = new \Twig_Environment( $loader );
  $template = $twig->loadTemplate( 'index.html' );
  echo $template->render(
    array( 'jquery_file' => JQUERY_FILE,
           'jquery_ui_file' => JQUERY_UI_FILE,
           'jquery_layout_file' => JQUERY_LAYOUT_FILE ) );
}
catch( \Exception $e )
{
  // TODO: need to handle exceptions properly
  log::singleton()->err( "Uncaught ".$e->__toString() );
}
?>
