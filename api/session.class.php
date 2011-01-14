<?php
/**
 * session.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth
 */

namespace sabretooth;

require_once ADODB_PATH.'/adodb-exceptions.inc.php';
require_once ADODB_PATH.'/adodb-errorhandler.inc.php';
require_once ADODB_PATH.'/adodb.inc.php';

/**
 * session: handles all session-based information
 *
 * The session class is used to track all information from the time a user logs into the system
 * until they log out.
 * This class is a singleton, instead of using the new operator call {@singleton() 
 * @package sabretooth
 */
final class session extends singleton
{
  /**
   * Constructor.
   * 
   * Since this class uses the singleton pattern the constructor is never called directly.  Instead
   * use the {@link singleton} method.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access protected
   */
  protected function __construct( $arguments )
  {
    // WARNING!  When we construct the session we haven't finished setting up the system yet, so
    // don't use the log class in this method!
    
    // one and only one argument should be past to the constructor
    assert( isset( $arguments ) && 1 == count( $arguments ) );
    
    // the first argument is the settings array from an .ini file
    $settings = $arguments[0];
    
    // make sure we have all necessary categories
    assert( isset( $settings[ 'general' ] ) && is_array( $settings[ 'general' ] ) &&
            isset( $settings[ 'db' ] ) && is_array( $settings[ 'general' ] ) );
    
    // copy the setting one category at a time, ignore any unknown categories
    $this->settings[ 'general' ] = $settings[ 'general' ];
    $this->settings[ 'db' ] = $settings[ 'db' ];

    // set error reporting
    error_reporting(
      $this->settings[ 'general' ][ 'development_mode' ] ? E_ALL | E_STRICT : E_ALL );
  }

  public function initialize()
  {
    // set up the database
    $this->db = ADONewConnection( session::singleton()->get_setting( 'db', 'driver' ) );
    
    if( false == $this->db->Connect(
      session::singleton()->get_setting( 'db', 'server' ),
      session::singleton()->get_setting( 'db', 'username' ),
      session::singleton()->get_setting( 'db', 'password' ),
      session::singleton()->get_setting( 'db', 'database' ) ) )
    {
      log::singleton()->alert( 'Unable to connect to the database.' );
    }
    $this->db->SetFetchMode( ADODB_FETCH_ASSOC );

    // TODO: automatic primary site detection ('east' is just a placeholder)
    $site_name = 'east';
    $this->site = database\site::get_unique_record( 'name', $site_name );
    if( NULL == $this->site )
      log::singleton()->err( "Site '$site_name' not found." );

    // determine the site and user database objects.
    $this->user = database\user::get_unique_record( 'name', $_SERVER[ 'REMOTE_USER' ] );
    if( NULL == $this->user )
      log::singleton()->err( 'User "'.$_SERVER[ 'REMOTE_USER' ].'" not found.', NULL, 0 );
  }
  
  /**
   * Get the value of an .ini setting.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $category The category the setting belongs to.
   * @param string $name The name of the setting.
   * @access public
   */
  public function get_setting( $category, $name )
  {
    $value = NULL;
    if( !isset( $this->settings[ $category ] ) ||
        !isset( $this->settings[ $category ][ $name ] ) )
    {
      log::singleton()->warning(
        "Tried getting value for setting [$category][$name] which doesn't exist." );
    }
    else
    {
      $value = $this->settings[ $category ][ $name ];
    }

    return $value;
  }

  /**
   * Get the current user.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return database\user
   * @access public
   */
  public function get_user() { return $this->user; }

  /**
   * Get the current site.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return database\site
   * @access public
   */
  public function get_db()
  {
    return $this->db;
  }

  /**
   * Get the database resource
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return database\site
   * @access public
   */
  public function get_site() { return $this->site; }

  /**
   * An array which holds .ini settings.
   * @var array( mixed )
   * @access private
   */
  private $settings = array();

  /**
   * A reference to the ADODB resource.
   * @var resource
   * @access private
   */
  private $db = NULL;

  /**
   * The active record of the current user.
   * @var database\user
   * @access private
   */
  private $user = NULL;

  /**
   * The active record of the current site.
   * @var database\site
   * @access private
   */
  private $site = NULL;
}
?>
