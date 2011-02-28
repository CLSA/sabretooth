<?php
/**
 * sabretooth.inc.php
 * 
 * Functions and setup code required by all web scripts.
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */
namespace sabretooth;
session_name( dirname( __FILE__ ) );
session_start();
$_SESSION['time']['script_start_time'] = microtime( true );

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
      throw new \Exception( "<pre>\n".
           "FATAL ERROR: Unable to find required file '$file'.\n".
           "Please check that paths in the sabretooth ini are set correctly.\n".
           "</pre>\n" );
    }
  }
  include $file;
}

// Function to get information from the limesurvey config file
function get_limesurvey_database_info()
{
  $file = LIMESURVEY_PATH.'/config.php';
  if( !file_exists( $file ) )
  {
    throw new \Exception( "<pre>\n".
         "FATAL ERROR: Unable to find limesurvey's config.php file '$file'.\n".
         "Please check that paths in the sabretooth ini are set correctly.\n".
         "</pre>\n" );
  }

  include $file;

  return array( 'driver' => $databasetype,
                'server' => $databaselocation,
                'username' => $databaseuser,
                'password' => $databasepass,
                'database' => $databasename,
                'prefix' => $dbprefix );
}

// load the default, then local settings, then define various settings
include_file( 'sabretooth.ini.php' );
include_file( 'sabretooth.local.ini.php', true );

$SETTINGS[ 'path' ][ 'API' ] = $SETTINGS[ 'path' ][ 'SABRETOOTH' ].'/api';
$SETTINGS[ 'path' ][ 'DOC' ] = $SETTINGS[ 'path' ][ 'SABRETOOTH' ].'/doc';
$SETTINGS[ 'path' ][ 'SQL' ] = $SETTINGS[ 'path' ][ 'SABRETOOTH' ].'/sql';
$SETTINGS[ 'path' ][ 'TPL' ] = $SETTINGS[ 'path' ][ 'SABRETOOTH' ].'/tpl';

foreach( $SETTINGS[ 'path' ] as $path_name => $path_value ) define( $path_name.'_PATH', $path_value );
foreach( $SETTINGS[ 'url' ] as $path_name => $path_value ) define( $path_name.'_URL', $path_value );

// get limesurvey's database info
$SETTINGS[ 'survey_db' ] = get_limesurvey_database_info();

// include necessary files
include_file( API_PATH.'/exception/error_codes.inc.php' );
include_file( API_PATH.'/autoloader.class.php' );

// registers an autoloader so classes don't have to be included manually
autoloader::register();

// set up the session and send a notice of what's happening
$session = session::self( $SETTINGS );
$session->initialize();
log::notice( 'running script: "'.$SETTINGS[ 'url' ][ 'FULL' ].'"' );
?>
