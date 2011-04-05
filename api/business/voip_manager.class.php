<?php
/**
 * voip_manager.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\business
 * @filesource
 */

namespace sabretooth\business;

require_once SHIFT8_PATH.'/library/Shift8.php';

/**
 * Manages VoIP communications.
 * 
 * @package sabretooth\business
 */
class voip_manager extends \sabretooth\singleton
{
  /**
   * Constructor.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function __construct()
  {
    $session = \sabretooth\business\session::self();
    $this->enabled = true === $session->get_setting( 'voip', 'enabled' );
    $this->url = $session->get_setting( 'voip', 'url' );
    $this->username = $session->get_setting( 'voip', 'username' );
    $this->password = $session->get_setting( 'voip', 'password' );
    $this->prefix = $session->get_setting( 'voip', 'prefix' );
  }
    
  /**
   * Initializes the voip manager.
   * 
   * This method should be called immediately after initial construction of the manager
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @throws exception\runtime, exception\voip
   * @access public
   */
  public function initialize()
  {
    if( !$this->enabled ) return;

    try
    {
      // create and connect to the shift8 AJAM interface
      $this->manager = new \Shift8( $this->url, $this->username, $this->password );
      if( !$this->manager->login() )
        throw new \sabretooth\exception\runtime(
          'Unable to connect to the Asterisk server.', __METHOD__ );

      // get the current SIP info
      $peer = \sabretooth\business\session::self()->get_user()->name;
      $s8_event = $this->manager->getSIPPeer( $peer );
      
      if( !is_null( $s8_event ) &&
          $peer == $s8_event->get( 'objectname' ) &&
          'OK' == substr( $s8_event->get( 'status' ), 0, 2 ) )
      {
        $this->sip_info = array(
          'status' => $s8_event->get( 'status' ),
          'type' => $s8_event->get( 'channeltype' ),
          'agent' => $s8_event->get( 'sip_useragent' ),
          'ip' => $s8_event->get( 'address_ip' ),
          'port' => $s8_event->get( 'address_port' ) );
      }
    }
    catch( \Shift8_Exception $e )
    {
      throw new \sabretooth\exception\voip(
        'Failed to initialize Asterisk AJAM interface.', __METHOD__, $e );
    }
  }
  
  /**
   * Gets a list of all active calls.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string|array $peers A peer or array or peers to restrict the list to.
   * @return array( voip_call )
   * @access public
   */
  public function get_calls( $peers = NULL )
  {
    if( !$this->enabled ) return array();
    if( is_null( $this->call_list ) ) $this->get_calls_from_server();

    if( is_null( $peers ) ) return $this->call_list;

    // build the call list
    $calls = array();
    foreach( $this->call_list as $voip_call )
      if( ( is_string( $peers ) && $peers == $voip_call->get_peer() ) ||
          ( is_array( $peers ) && in_array( $voip_call->get_peer(), $peers ) ) )
        $calls[] = $voip_call;

    return $calls;
  }
  
  /**
   * Reads the list of active calls from the server.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @throws exception\voip
   * @access private
   */
  private function get_calls_from_server()
  {
      $this->call_list = array();
      $events = $this->manager->getStatus();

      if( is_null( $events ) )
        throw new \sabretooth\exception\voip(
          $this->manager->getLastError(), __METHOD__ );

      foreach( $events as $s8_event )
        if( 'Status' == $s8_event->get( 'event' ) )
          $this->call_list[] = new voip_call( $s8_event );
  }
  
  /**
   * Attempts to connect to a contact.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\contact $db_contact
   * @return voip_call
   * @access public
   * @throws exception\argument, exception\runtime, exception\notice, exception\voip
   */
  public function call( $db_contact )
  {
    if( !$this->enabled ) return;

    // check that the contact is valid
    if( is_null( $db_contact ) ||
        !is_object( $db_contact ) ||
        'sabretooth\\database\\contact' != get_class( $db_contact ) )
      throw \sabretooth\exception\argument( 'db_contact', $db_contact, __METHOD__ );

    // check that the phone number has exactly 10 digits
    $digits = preg_replace( '/[^0-9]/', '', $db_contact->phone );
    if( 10 != strlen( $digits ) )
      throw \sabretooth\exception\runtime(
        'Tried to connect to phone number which does not have exactly 10 digits.', __METHOD__ );

    $peer = \sabretooth\business\session::self()->get_user()->name;
    
    // make sure the user isn't already in a call
    if( 0 < count( $this->get_calls( $peer ) ) )
      throw \sabretooth\exception\notice(
        'Unable to connect call since you already appear to be in a call.', __METHOD__ );

    // originate call (careful, the online API has the arguments in the wrong order)
    $channel = 'SIP/'.$peer;
    $context = 'users';
    $extension = $this->prefix.$digits;
    $priority = 1;
    if( !$this->manager->originate( $channel, $context, $extension, $priority ) )
      throw new \sabretooth\exception\voip(
        $this->manager->getLastError(), __METHOD__ );

    // rebuild the call list
    $this->get_calls_from_server();
  }

  /**
   * Disconnects a call (does nothing if already disconnected).
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param voip_call $voip_call The call to disconnect.  If this parameter is null then all
   *                  calls who's peer is the current user are disconnected instead.
   * @access public
   */
  public function hang_up( $voip_call = NULL )
  {
    if( !$this->enabled ) return;
    
    $rebuild_list = false;

    if( is_null( $voip_call ) )
    {
      $peer = \sabretooth\business\session::self()->get_user()->name;
      foreach( $this->get_calls( $peer ) as $voip_call )
        $rebuild_list = $rebuild_list || $this->manager->hangup( $voip_call->get_channel() );
    }
    else
    {
      $rebuild_list = $this->manager->hangup( $voip_call->get_channel() );
    }

    // rebuild the call list
    if( $rebuild_list ) $this->get_calls_from_server();
  }

  /**
   * Determines whether a SIP connection is detected with the client
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return boolean
   * @access public
   */
  public function get_sip_enabled()
  {
    return $this->enabled && is_array( $this->sip_info ) && 0 < count( $this->sip_info );
  }
  
  /**
   * Whether VOIP is enabled.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return boolean
   * @access public
   */
  public function get_enabled() { return $this->enabled; }

  /**
   * Gets the dialing prefix to use when placing external calls
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return string
   * @access public
   */
  public function get_prefix() { return $this->prefix; }

  /**
   * The asterisk manager object
   * @var Shift8 object
   * @access private
   */
  private $manager = NULL;

  /**
   * The current SIP information (empty array if there is no connection found)
   * @var array
   * @access private
   */
  private $sip_info = NULL;

  /**
   * An array of all currently active calls.
   * 
   * @var array( voip_call )
   * @access private
   */
  private $call_list = NULL;

  /**
   * Whether VOIP is enabled.
   * @var string
   * @access private
   */
  private $enabled = false;
  
  /**
   * The url that asterisk's AJAM is running on
   * @var string
   * @access private
   */
  private $url = '';
  
  /**
   * Which username to use when connecting to the manager
   * @var string
   * @access private
   */
  private $username = '';
  
  /**
   * Which password to use when connecting to the manager
   * @var string
   * @access private
   */
  private $password = '';
  
  /**
   * The dialing prefix to use when making external calls.
   * @var string
   * @access private
   */
  private $prefix = '';
}
?>
