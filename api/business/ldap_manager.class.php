<?php
/**
 * ldap_manager.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\business;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * Manages LDAP entries
 */
class ldap_manager extends \cenozo\business\ldap_manager
{
  /**
   * Extend parent method in order to add asterisk details.
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
      throw lib::create( 'exception\ldap',
        ldap_error( $this->resource ), ldap_errno( $this->resource ) );
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
      throw lib::create( 'exception\ldap',
        ldap_error( $this->resource ), ldap_errno( $this->resource ) );
    
    $entries = @ldap_get_entries( $this->resource, $search );
    ldap_free_result( $search );
    if( !$entries )
      throw lib::create( 'exception\ldap',
        ldap_error( $this->resource ), ldap_errno( $this->resource ) );
    
    if( 0 == $entries['count'] )
      throw lib::create( 'exception\runtime', 'LDAP user '.$username.' not found.', __METHOD__ );
    
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
      throw lib::create( 'exception\ldap',
        ldap_error( $this->resource ), ldap_errno( $this->resource ) );
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
      throw lib::create( 'exception\ldap',
        ldap_error( $this->resource ), ldap_errno( $this->resource ) );
    
    $entries = @ldap_get_entries( $this->resource, $search );
    ldap_free_result( $search );
    if( !$entries )
      throw lib::create( 'exception\ldap',
        ldap_error( $this->resource ), ldap_errno( $this->resource ) );
    
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
}
?>
