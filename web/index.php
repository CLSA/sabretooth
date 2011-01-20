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
define( 'ACTION_MODE', false );

if( isset( $_POST ) && isset( $_POST['logout'] ) && 'logout' == $_POST['logout'] )
{
  $method = 'http'.( 'on' == $_SERVER['HTTPS'] ? 's' : '' );
  header( 'Location: '.$method.'://none:none@'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'] );
}

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
  
  assert_options( ASSERT_ACTIVE, util::devel_mode() ? 1 : 0 );
  assert_options( ASSERT_WARNING, 0 );

  // set up the template engine
  $loader = new \Twig_Loader_Filesystem( TPL_PATH );
  $twig = new \Twig_Environment( $loader, array( 'debug' => util::devel_mode(),
                                                 'strict_variables' => util::devel_mode() ) );
  $twig->addFilter( 'count', new \Twig_Filter_Function( 'count' ) );
  foreach( $SETTINGS[ 'paths' ] as $path_name => $path_value )
    $twig->addGlobal( $path_name, $path_value );
  $twig_template = $twig->loadTemplate( 'index.html' );
  
  // create and setup the index widget
  $w_index = new ui\index();
  $w_index->set_variable( 'survey_active', false );

  $output = $twig_template->render( ui\widget::get_variables() );
  print $output;
}
// TODO: need to handle exceptions properly when in development mode using error dialogs
catch( exception\database $e )
{
  log::singleton()->err( "Database ".$e->__toString() );
}
catch( \Twig_Error_Runtime $e )
{
  log::singleton()->err( "Template ".$e->__toString() );
}
catch( \Exception $e )
{
  log::singleton()->err( "Uncaught ".$e->__toString() );
}
?>
