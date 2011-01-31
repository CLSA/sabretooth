<?php
/**
 * sabretooth.inc.php
 * 
 * Functions and setup code required by all web scripts.
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */
namespace sabretooth;
session_name( 'sabretooth' );
session_start();

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

// load the default, then local settings, then define paths and urls
include_file( 'sabretooth.ini.php' );
include_file( 'sabretooth.local.ini.php', true );
foreach( $SETTINGS[ 'path' ] as $path_name => $path_value ) define( $path_name.'_PATH', $path_value );
foreach( $SETTINGS[ 'url' ] as $path_name => $path_value ) define( $path_name.'_URL', $path_value );

// include necessary files
include_file( API_PATH.'/autoloader.class.php' );

// registers an autoloader so classes don't have to be included manually
autoloader::register();

// set up the session
$session = session::self( $SETTINGS );
$session->initialize();

// set up asserts
assert_options( ASSERT_ACTIVE, util::in_devel_mode() ? 1 : 0 );
assert_options( ASSERT_WARNING, 0 );
?>
