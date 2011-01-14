<?php
/**
 * action.php
 * 
 * Web script which can be called to perform operations on the system.
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @vertion 0.1
 */
namespace sabretooth;

require_once 'sabretooth.ini.php';
if( file_exists( 'sabretooth.local.ini.php' ) ) 
{
  require_once 'sabretooth.local.ini.php';
}

try
{
  // TODO: determine operation, site and user
  $operation = '';
  $session = session::singleton();

  // create an operation manager and try executing the operation
  $oman = new business\operation_manager();
  $oman->execute( $operation, $session->get_site(), $session->get_user() );
}
catch( \Exception $e )
{
  // if we get an exception here then it is unknown
  // TODO: give a generic message to the user that something went wrong
  
}
?>
