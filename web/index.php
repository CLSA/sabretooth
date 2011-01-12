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

// determine what errors to display
ini_set( 'display_errors', $SETTINGS[ 'development_mode' ] ? '1' : '0' );
error_reporting( $SETTINGS[ 'development_mode' ] ? E_ALL | E_STRICT : E_ALL );

// required classes
require_once $SETTINGS[ 'api_path' ].'/util.class.php';

$SETTINGS['Array'] = true;

util::var_dump_html( $SETTINGS );

echo DIRECTORY_SEPARATOR;
require_once $SETTINGS[ 'api_path' ].'/business/session.class.php';
/*

try
{
  print "yeehaw!";
  // TODO: determine operation
  //$session = business\session::get_instance();
}
catch( exception $e )
{
}
*/
?>
