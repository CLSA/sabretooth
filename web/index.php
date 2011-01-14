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

// load the settings
require_once 'sabretooth.ini.php';
if( file_exists( 'sabretooth.local.ini.php' ) ) 
{
  require_once 'sabretooth.local.ini.php';
}

// define all paths
foreach( $SETTINGS[ 'paths' ] as $path_name => $path_value )
{
  define( $path_name, $path_value );
}

// set up error handling
ini_set( 'display_errors', '0' );
// error_reporting is also called in session's constructor
error_reporting( E_ALL | E_STRICT );

// remaining required classes
require_once API_PATH.'/log.class.php';
require_once API_PATH.'/session.class.php';

try
{
  // set up the session
  $session = session::singleton( $SETTINGS );
  $session->initialize();
  util::var_dump_html( $session );
}
catch( \Exception $e )
{
  // TODO: need to handle exceptions properly
  log::singleton()->err( "Uncaught ".$e->__toString() );
}
?>
