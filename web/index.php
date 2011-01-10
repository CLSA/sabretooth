<?php
/**
 * index.php
 * 
 * Main web script which drives the application.
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @vertion 0.1
 */
require_once 'sabretooth.ini.php';
if( file_exists( 'sabretooth.local.ini.php' ) ) 
{
  require_once 'sabretooth.local.ini.php';
}

use sabretooth\business as business;
use sabretooth\database as database;
use sabretooth\exception as exception;
use sabretooth\interface as interface;

require_once $SETTINGS[ 'api_path' ].'/business/session.class.php';

try
{
  // TODO: determine operation
  $session = business\session::get_instance();
}
catch( exception $e )
{
}
?>
