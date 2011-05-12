<?php
/**
 * ldap_manager.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\business
 * @filesource
 */

namespace sabretooth\business;
use sabretooth\log, sabretooth\util;
use sabretooth\database as db;
use sabretooth\exception as exc;

require_once SHIFT8_PATH.'/library/Shift8.php';

/**
 * Manages LDAP entries
 * 
 * @package sabretooth\business
 */
class ldap_manager extends \sabretooth\singleton
{
  /**
   * Constructor.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function __construct()
  {
    $session = session::self();
    $this->server = $session->get_setting( 'ldap', 'server' );
    $this->port = $session->get_setting( 'ldap', 'port' );
    $this->base = $session->get_setting( 'ldap', 'base' );
    $this->username = $session->get_setting( 'ldap', 'username' );
    $this->password = $session->get_setting( 'ldap', 'password' );
    $this->active_directory = $session->get_setting( 'ldap', 'active_directory' );
  }

  /**
   * Destructor which unbinds the LDAP connection, if one exists
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function __destruct()
  {
    if( is_resource( $this->resource ) ) @ldap_unbind( $this->resource );
    $this->resource = NULL;
  }
    
  /**
   * Initializes the ldap manager.
   * This method is called internally by the class whenever necessary.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @throws exception\ldap
   * @access public
   */
  private function connect()
  {
    if( is_resource( $this->resource ) ) return;

    $this->resource = ldap_connect( $this->server, $this->port );
    if( $this->active_directory )
    {
      if( false == @ldap_set_option( $this->resource, LDAP_OPT_PROTOCOL_VERSION, 3 ) )
        throw new exc\ldap( ldap_error( $this->resource ), ldap_errno( $this->resource ) );
    }

    if( !( @ldap_bind( $this->resource, $this->username, $this->password ) ) )
      throw new exc\ldap( ldap_error( $this->resource ), ldap_errno( $this->resource ) );
  }
  
  /**
   * Creates a new user.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $username The new username to create
   * @param string $first_name The user's first name
   * @param string $last_name The user's last name
   * @param string $password The initial password for the account
   * @throws exception\ldap
   * @access public
   */
  public function new_user( $username, $first_name, $last_name, $password )
  {
    $this->connect();

    $uid = (string) $this->get_next_asterisk_uid();
    $data = array (
      'cn' => $first_name.' '.$last_name,
      'sn' => $last_name,
      'givenname' => $first_name,
      'uid' => $username,
      'objectClass' => array (
        'inetOrgPerson',
        'passwordHolder',
        'AsteriskSIPUser',
        'AsteriskVoiceMail',
        'AsteriskQueueMember' ),
      'description' => 'clsa',
      'astaccounttype' => 'friend',
      'astaccountcontext' => 'users',
      'astaccountcallerid' => $uid,
      'astaccountmailbox' => $uid,
      'astaccounthost' => 'dynamic',
      'astaccountnat' => 'yes',
      'astaccountqualify' => 'yes',
      'astaccountcanreinvite' => 'no',
      'astaccountdtmfmode' => 'rfc2833',
      'astaccountinsecure' => 'port',
      'astaccountregistrationserver' => '0',
      'astcontext' => 'users',
      'astvoicemailmailbox' => $uid,
      'astvoicemailpassword' => $uid,
      'astvoicemailemail' => 'user@domain',
      'astvoicemailattach' => 'yes',
      'astvoicemaildelete' => 'no',
      'astqueuemembername' => $username,
      'astqueueinterface' => 'SIP/'.$username,
      'userpassword' => util::sha1_hash( $password ),
      'eboxsha1password' => util::sha1_hash( $password ),
      'eboxmd5password' => util::md5_hash( $password ),
      'eboxlmpassword' => util::lm_hash( $password ),
      'eboxntpassword' => util::ntlm_hash( $password ),
      'eboxdigestpassword' => util::md5_hash( sprintf( '%s:ebox:%s', $username, $password ) ),
      'eboxrealmpassword' => '{MD5}'.md5( sprintf( '%s:ebox:%s', $username, $password ) ),
      'astaccountipaddress' => '0.0.0.0',
      'astaccountport' => '0',
      'astaccountexpirationtimestamp' => '0',
      'astaccountlastqualifymilliseconds' => '0' );
    
    $dn = sprintf( 'uid=%s,ou=Users,%s', $username, $this->base );
    if( !( @ldap_add( $this->resource, $dn, $data ) ) )
      throw new exc\ldap( ldap_error( $this->resource ), ldap_errno( $this->resource ) );
  }

  /**
   * Deletes a user.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $username The username to delete
   * @throws exception\ldap
   * @access public
   */
  public function delete_user( $username )
  {
    $this->connect();
    
    $dn = sprintf( 'uid=%s,ou=Users,%s', $username, $this->base );
    if( !( @ldap_delete( $this->resource, $dn ) ) )
      throw new exc\ldap( ldap_error( $this->resource ), ldap_errno( $this->resource ) );
  }

  /**
   * Sets a user's password
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $username Which user to affect
   * @param string $password The new password for the account
   * @throws exception\ldap, exception\runtime
   * @access public
   */
  public function set_user_password( $username, $password )
  {
    $this->connect();

    $search = @ldap_search( $this->resource, $this->base, sprintf( '(&(uid=%s))', $username ) );
    if( !$search )
      throw new exc\ldap( ldap_error( $this->resource ), ldap_errno( $this->resource ) );
    
    $entries = @ldap_get_entries( $this->resource, $search );
    ldap_free_result( $search );
    if( !$entries )
      throw new exc\ldap( ldap_error( $this->resource ), ldap_errno( $this->resource ) );
    
    if( 0 == $entries['count'] )
      throw new exc\runtime( 'LDAP user '.$username.' not found.', __METHOD__ );
    
    $data = array(
      'userpassword' => util::sha1_hash( $password ),
      'eboxsha1password' => util::sha1_hash( $password ),
      'eboxmd5password' => util::md5_hash( $password ),
      'eboxlmpassword' => util::lm_hash( $password ),
      'eboxntpassword' => util::ntlm_hash( $password ),
      'eboxdigestpassword' => util::md5_hash( sprintf( '%s:ebox:%s', $username, $password ) ),
      'eboxrealmpassword' => '{MD5}'.md5( sprintf( '%s:ebox:%s', $username, $password ) ) );
  
    $dn = $entries[0]['dn'];
    if( !( @ldap_mod_replace( $this->resource, $dn, $data ) ) )
      throw new exc\ldap( ldap_error( $this->resource ), ldap_errno( $this->resource ) );
  }

  /**
   * Gets the next available asterisk uid number.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @throws exception\ldap
   * @return integer
   * @access private
   */
  private function get_next_asterisk_uid()
  {
    $this->connect();
    
    $search = @ldap_search( $this->resource, $this->base, '(&(uid=*))' );
    if( !$search )
      throw new exc\ldap( ldap_error( $this->resource ), ldap_errno( $this->resource ) );
    
    $entries = @ldap_get_entries( $this->resource, $search );
    ldap_free_result( $search );
    if( !$entries )
      throw new exc\ldap( ldap_error( $this->resource ), ldap_errno( $this->resource ) );
    
    $max_id = 999;
    foreach( $entries as $index => $entry )
    {
      if( !is_int( $index ) ) continue;
      if( array_key_exists( 'astaccountcallerid', $entry ) )
      {
        $id = $entry['astaccountcallerid'][0];
        if( $max_id < $id ) $max_id = $id;
      }
    }
  
    return $max_id + 1;
  }

  /**
   * The PHP LDAP resource.
   * @var resource
   * @access private
   */
  private $resource = NULL;
  
  /**
   * The LDAP server to connect to.
   * @var string
   * @access private
   */
  private $server = 'localhost';
  
  /**
   * The LDAP port to connect to.
   * @var integer
   * @access private
   */
  private $port = 389;
  
  /**
   * The base dn to use when searching
   * @var string
   * @access private
   */
  private $base = '';
  
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
   * Whether the server is in active directory mode.
   * @var bool
   * @access private
   */
  private $active_directory = false;
}
?>
