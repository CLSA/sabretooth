<?php
/**
 * cenozo_manager.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package cenozo\business
 * @filesource
 */

namespace sabretooth\business;
use sabretooth\log, sabretooth\util;
use sabretooth\database as db;
use sabretooth\exception as exc;

/**
 * Manages communication with other cenozo services.
 * 
 * @package sabretooth\business
 */
class cenozo_manager extends \sabretooth\factory
{
  /**
   * Constructor.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access protected
   */
  protected function __construct( $arguments )
  {
    // determine whether connecting to cenozo service is enabled
    $url = $arguments[0];
    $this->enabled = !is_null( $url );

    if( $this->enabled )
    {
      $base_url = $url.'/';
      $base_url = preg_replace(
        '#://#', '://'.$_SERVER['PHP_AUTH_USER'].':'.$_SERVER['PHP_AUTH_PW'].'@', $base_url );
      $this->base_url = $base_url;
    }
  }
  
  /**
   * Determines if Cenozo is enabled.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return boolean
   * @access public
   */
  public function is_enabled()
  {
    return $this->enabled;
  }
  
  /**
   * Logs into Cenozo via HTTP POST
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access protected
   */
  protected function login()
  {
    if( !$this->enabled || $this->logged_in ) return;

    // log in using the current user/role/site
    $this->set_site( session::self()->get_site() );
    $this->set_role( session::self()->get_role() );
      
    $this->logged_in = true;
  }
  
  /**
   * Set the current user's site at the remote application.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access protected
   */
  protected function set_site( $db_site )
  {
    $request = new \HttpRequest();
    $request->enableCookies();
    $request->setUrl( $this->base_url.'self/set_site' );
    $request->setMethod( \HttpRequest::METH_POST );
    $request->setPostFields(
      array( 'noid' => array( 'site.name' => $db_site->name, 'site.cohort' => 'tracking' ) ) );
    static::send( $request );

  }

  /**
   * Set the current user's role at the remote application.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access protected
   */
  protected function set_role( $db_role )
  {
    $request = new \HttpRequest();
    $request->enableCookies();
    $request->setUrl( $this->base_url.'self/set_role' );
    $request->setMethod( \HttpRequest::METH_POST );
    $request->setPostFields(
      array( 'noid' => array( 'role.name' => $db_role->name ) ) );
    static::send( $request );
  }

  /**
   * Pulls information from Cenozo via HTTP GET
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
   * Pushes information to Cenozo via HTTP POST
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
   * Sends an HTTP request to Cenozo.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param \HttpRequest $request The request to send
   * @throws exception\cenozo, exception\runtime
   * @return \HttpMessage
   * @access protected
   */
  protected static function send( $request )
  {
    $message = $request->send();
    $code = $message->getResponseCode();

    if( 400 == $code )
    { // duplicate cenozo exception
      $body = json_decode( $message->body );
      throw new exc\cenozo( $body->error_type, $body->error_code, $body->error_message );
    }
    else if( 200 != $code )
    { // A non-cenozo error has happened
      throw new exc\runtime( 'Unable to connect to Cenozo (code: '.$code.')', __METHOD__ );
    }

    return $message;
  }

  /**
   * Whether or not Cenozo is enabled
   * @var boolean
   * @access protected
   */
  protected $enbled = false;

  /**
   * The base URL to Cenozo
   * @var string
   * @access protected
   */
  protected $base_url = NULL;

  /**
   * Whether Cenozo has been logged into or not
   * @var boolean
   * @access protected
   */
  protected $logged_in = false;
}
