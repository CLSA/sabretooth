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
  
  /**
   * Determines if Mastodon is enabled.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return boolean
   * @access public
   */
  public function is_enabled()
  {
    return $this->enabled;
  }
  
  /**
   * Logs into Mastodon via HTTP POST
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
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
    $request->setPostFields(
      array( 'noid' => array( 'site.name' => $db_site->name, 'site.cohort' => 'tracking' ) ) );
    static::send( $request );

    // set the role
    $request->setUrl( $this->base_url.'self/set_role' );
    $request->setMethod( \HttpRequest::METH_POST );
    $request->setPostFields(
      array( 'noid' => array( 'role.name' => $db_role->name ) ) );
    static::send( $request );
      
    $this->logged_in = true;
  }

  /**
   * Pulls information from Mastodon via HTTP GET
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $subject The pull's subject
   * @param string $name The pull's name
   * @param array $arguments The query data
   * @throws exception\argument
   * @return \StdObject
   * @access public
   */
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
    
    $message = static::send( $request );
    return json_decode( $message->body );
  }

  /**
   * Pushes information to Mastodon via HTTP POST
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $subject The push's subject
   * @param string $name The push's name
   * @param array $arguments The post fields
   * @throws exception\argument
   * @access public
   */
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

    static::send( $request );
  }

  /**
   * Sends an HTTP request to Mastodon.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param \HttpRequest $request The request to send
   * @throws exception\mastodon, exception\runtime
   * @return \HttpMessage
   * @access protected
   */
  protected static function send( $request )
  {
    $message = $request->send();
    $code = $message->getResponseCode();

    if( 400 == $code )
    { // duplicate mastodon exception
      $body = json_decode( $message->body );
      throw new exc\mastodon( $body->error_type, $body->error_code, $body->error_message );
    }
    else if( 200 != $code )
    { // A non-mastodon error has happened
      throw new exc\runtime( 'Unable to connect to Mastodon (code: '.$code.')', __METHOD__ );
    }

    return $message;
  }

  /**
   * Whether or not Mastodon is enabled
   * @var boolean
   * @access protected
   */
  protected $enbled = false;

  /**
   * The base URL to Mastodon
   * @var string
   * @access protected
   */
  protected $base_url = NULL;

  /**
   * Whether Mastodon has been logged into or not
   * @var boolean
   * @access protected
   */
  protected $logged_in = false;
}
