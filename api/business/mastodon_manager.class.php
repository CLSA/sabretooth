<?php
/**
 * mastodon_manager.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package mastodon\business
 * @filesource
 */

namespace sabretooth\business;
use sabretooth\log, sabretooth\util;
use sabretooth\database as db;
use sabretooth\exception as exc;

/**
 * Manages communication with the mastodon service.
 * 
 * @package sabretooth\business
 */
class mastodon_manager extends \sabretooth\singleton
{
  /**
   * Constructor.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access protected
   */
  protected function __construct()
  {
    // determine whether connecting to mastodon is enabled
    $this->enabled = !is_null( MASTODON_URL );

    if( $this->enabled )
    {
      $base_url = SABRETOOTH_URL.'/'.MASTODON_URL.'/';
      $base_url = preg_replace(
        '#://#', '://'.$_SERVER['PHP_AUTH_USER'].':'.$_SERVER['PHP_AUTH_PW'].'@', $base_url );
      $this->base_url = $base_url;
    }
  }
  
  // TODO: document
  public function is_enabled()
  {
    return $this->enabled;
  }
  
  // TODO: document
  protected function login()
  {
    if( !$this->enabled || $this->logged_in ) return;

    // log in using the current user/role/site
    $db_site = session::self()->get_site();
    $db_role = session::self()->get_role();
    
    $request = new \HttpRequest();
    $request->enableCookies();

    // set the site
    $request->setUrl( $this->base_url.'self/set_site' );
    $request->setMethod( \HttpRequest::METH_POST );
    $request->setPostFields( array( 'name' => $db_site->name, 'cohort' => 'tracking' ) );
    if( 200 != $request->send()->getResponseCode() )
      throw new exc\runtime( 'Unable to connect to Mastodon', __METHOD__ );
    
    // set the role
    $request->setUrl( $this->base_url.'self/set_role' );
    $request->setMethod( \HttpRequest::METH_POST );
    $request->setPostFields( array( 'name' => $db_role->name ) );
    if( 200 != $request->send()->getResponseCode() )
      throw new exc\runtime( 'Unable to connect to Mastodon', __METHOD__ );
      
    $this->logged_in = true;
  }

  // TODO: document
  public function pull( $subject, $name, $arguments = NULL )
  {
    if( !$this->enabled ) return NULL;
    $this->login();
    
    $request = new \HttpRequest();
    $request->enableCookies();
    $request->setUrl( $this->base_url.$subject.'/'.$name );
    $request->setMethod( \HttpRequest::METH_GET );
    if( !is_null( $arguments ) )
    {
      if( !is_array( $arguments ) ) throw new exp\argument( 'arguments', $arguments, __METHOD__ );
      $request->setQueryData( $arguments );
    }
    
    $message = $request->send();
    if( 200 != $message->getResponseCode() )
      throw new exc\runtime( 'Unable to connect to Mastodon', __METHOD__ );

    return json_decode( $message->getBody() );
  }

  // TODO: document
  public function push( $subject, $name, $arguments = NULL )
  {
    if( !$this->enabled ) return;
    $this->login();

    $request = new \HttpRequest();
    $request->enableCookies();
    $request->setUrl( $this->base_url.$subject.'/'.$name );
    $request->setMethod( \HttpRequest::METH_POST );
    if( !is_null( $arguments ) )
    {
      if( !is_array( $arguments ) ) throw new exp\argument( 'arguments', $arguments, __METHOD__ );
      $request->setPostFields( $arguments );
    }

    if( 200 != $request->send()->getResponseCode() )
      throw new exc\runtime( 'Unable to connect to Mastodon', __METHOD__ );
  }

  // TODO: document
  protected $enbled = false;

  // TODO: document
  protected $base_url = NULL;

  // TODO: document
  protected $logged_in = false;
}
