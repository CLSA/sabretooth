<?php
/**
 * session.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth
 * @filesource
 */

namespace sabretooth;

/**
 * @category external
 */
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
    $categories = array( 'db',
                         'general',
                         'interface',
                         'version' );
    foreach( $categories as $category )
    {
      $this->settings[ $category ] = $settings[ $category ];
    }

    // set error reporting
    error_reporting(
      $this->settings[ 'general' ][ 'development_mode' ] ? E_ALL | E_STRICT : E_ALL );

    // setup the session array
    if( !isset( $_SESSION['slot'] ) ) $_SESSION['slot'] = array();
  }
  
  /**
   * Initializes the session.
   * 
   * This method should be called immediately after constructing the session.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function initialize()
  {
    // don't initialize more than once
    if( $this->initialized ) return;

    // set up the database
    $this->db = ADONewConnection( session::self()->get_setting( 'db', 'driver' ) );
    
    if( false == $this->db->Connect(
      session::self()->get_setting( 'db', 'server' ),
      session::self()->get_setting( 'db', 'username' ),
      session::self()->get_setting( 'db', 'password' ),
      session::self()->get_setting( 'db', 'database' ) ) )
    {
      log::alert( 'Unable to connect to the database.' );
    }
    $this->db->SetFetchMode( ADODB_FETCH_ASSOC );
    
    // determine the user (setting the user will also set the site and role)
    $user_name = $_SERVER[ 'PHP_AUTH_USER' ];
    $this->set_user( database\user::get_unique_record( 'name', $user_name ) );
    if( NULL == $this->user ) throw new exception\runtime( 'User "'.$user_name.'" not found.' );

    $this->initialized = true;
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
      log::warning(
        "Tried getting value for setting [$category][$name] which doesn't exist." );
    }
    else
    {
      $value = $this->settings[ $category ][ $name ];
    }

    return $value;
  }

  /**
   * Get the database resource.
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
   * Get the current role.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return database\role
   * @access public
   */
  public function get_role() { return $this->role; }

  /**
   * Get the current site.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return database\site
   * @access public
   */
  public function get_site() { return $this->site; }

  /**
   * Get the current user.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return database\user
   * @access public
   */
  public function get_user() { return $this->user; }

  /**
   * Set the current site and role.
   * 
   * If the user does not have the proper access then nothing is changed.  
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\site $db_site
   * @param database\role $db_role
   * @access public
   */
  public function set_site_and_role( $db_site, $db_role )
  {
    if( is_NULL( $db_site ) || is_NULL( $db_role ) )
    {
      $this->site = NULL;
      $this->role = NULL;
      unset( $_SESSION['current_site_id'] );
      unset( $_SESSION['current_role_id'] );
    }
    else
    {
      // verify that the user has the right access
      if( $this->user->has_access( $db_site, $db_role ) )
      {
        $this->site = $db_site;
        $this->role = $db_role;

        if( $_SESSION['current_site_id'] != $this->site->id ||
            $_SESSION['current_role_id'] != $this->role->id )
        {
          // clean out the slot stacks
          foreach( array_keys( $_SESSION['slot'] ) as $slot ) $this->slot_reset( $slot );
          $_SESSION['current_site_id'] = $this->site->id;
          $_SESSION['current_role_id'] = $this->role->id;
        }
      }
    }
  }

  /**
   * Set the current user.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\user $db_user
   * @access public
   */
  public function set_user( $db_user )
  {
    $this->user = $db_user;

    // Determine the site and role
    if( is_NULL( $this->user ) )
    {
      $this->set_site_and_role( NULL, NULL );
    }
    else
    {
      // do not use set functions or we will loose cookies
      $this->site = NULL;
      $this->role = NULL;

      // see if we already have the current site stored in the php session
      if( isset( $_SESSION['current_site_id'] ) && isset( $_SESSION['current_role_id'] ) )
      {
        $this->set_site_and_role( new database\site( $_SESSION['current_site_id'] ),
                                  new database\role( $_SESSION['current_role_id'] ) );
      }
      
      // if we still don't have a site and role then pick the first one we can find
      if( is_NULL( $this->site ) || is_NULL( $this->role ) )
      {
        $db_site_list = $this->user->get_site_list();
        if( 0 == count( $db_site_list ) )
          log::err( "User does not have access to any site." );
        $db_site = $db_site_list[0];
        $db_role_list = $this->user->get_role_list( $db_site );
        $db_role = $db_role_list[0];

        $this->set_site_and_role( $db_site, $db_role );
      }
    }
  }
  
  /**
   * Get the name of the current jquery-ui theme.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return string
   * @access public
   */
  public function get_theme()
  {
    $theme = $this->settings[ 'interface' ][ 'default_theme' ];

    if( !is_null( $this->user ) )
    {
      $user_theme = $this->user->theme;
      if( !is_null( $user_theme ) ) $theme = $user_theme;
    }

    return $theme;
  }
  
  /**
   * Add an operation to this user's activity log.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param ui\operation $operation The operation to log.
   * @param string $query URL query string passed to the operation
   * @access public
   */
  public function log_activity( $operation, $query )
  {
    // add the operation as activity
    $activity = new \sabretooth\database\activity();
    $activity->user_id = $this->user->id;
    $activity->site_id = $this->site->id;
    $activity->role_id = $this->role->id;
    $activity->operation_id = $operation->get_id();
    $activity->query = $query;
    $activity->save();
  }

  /**
   * Add a new widget to the slot's stack.
   * 
   * This method will delete any items after the current pointer, add the new widget to the end of
   * the stack then point to the new element.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $slot The name of the slot.
   * @param string $name The name of the widget.
   * @param array $args An associative array containing all widget arguments.
   * @access public
   */
  public function slot_push( $slot, $name, $args = NULL )
  {
    // make sure the slot's stack has been created
    $this->validate_slot( $slot ); 
    
    // get the current index and hack off whatever comes after it
    $last_widget = false;
    $index = $_SESSION['slot'][$slot]['stack']['index'];
    if( 0 <= $index )
    {
      array_slice( $_SESSION['slot'][$slot]['stack']['widgets'], 0, $index + 1 );
      $last_widget = end( $_SESSION['slot'][$slot]['stack']['widgets'] );
    }

    // now add this widget to the end, avoiding duplicates
    if( $last_widget && $name == $last_widget['name'] )
    {
      // update the args only
      $last_index = count( $_SESSION['slot'][$slot]['stack']['widgets'] ) - 1;
      $_SESSION['slot'][$slot]['stack']['widgets'][$last_index]['args'] = $args;
    }
    else // no duplicate, add the widget to the end of the stack
    {
      array_push( $_SESSION['slot'][$slot]['stack']['widgets'],
                  array( 'name' => $name,
                         'args' => $args ) );
    }

    $total = count( $_SESSION['slot'][$slot]['stack']['widgets'] );
    $_SESSION['slot'][$slot]['stack']['index'] = $total - 1;
    
    $this->update_slot_cookies();
  }

  /**
   * Returns whether or not there is a previous widget available.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return bool
   * @access public
   */
  public function slot_has_prev( $slot )
  {
    $index = $_SESSION['slot'][$slot]['stack']['index'];
    return -1 != $index && 0 <= ( $index - 1 );
  }

  /**
   * Returns whether or not there is a next widget available.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return bool
   * @access public
   */
  public function slot_has_next( $slot )
  {
    $index = $_SESSION['slot'][$slot]['stack']['index'];
    $total = count( $_SESSION['slot'][$slot]['stack']['widgets'] );
    return -1 != $index && $total > ( $index + 1 );
  }

  /**
   * Reverse the slot pointer by one.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $slot The name of the slot.
   * @return string The name of the previous widget (or NULL if there is no next widget).
   * @access public
   */
  public function slot_prev( $slot )
  {
    // make sure the slot's stack has been created
    $this->validate_slot( $slot ); 

    $value = NULL;
    
    // make sure to only decrement the index if we don't go out of bounds
    if( $this->slot_has_prev( $slot ) )
    {
      $new_index = $_SESSION['slot'][$slot]['stack']['index'] - 1;
      // update the stack index
      $_SESSION['slot'][$slot]['stack']['index'] = $new_index;
      // get the (now) previous item
      $value = $this->slot_current( $slot );
      $this->update_slot_cookies();
    }

    return $value;
  }
  
  /**
   * Advance the slot pointer by one.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $slot The name of the slot.
   * @return string The name of the next widget (or NULL if there is no next widget).
   * @access public
   */
  public function slot_next( $slot )
  {
    // make sure the slot's stack has been created
    $this->validate_slot( $slot ); 

    $value = NULL;
    
    // make sure to only increment the index if we don't go out of bounds
    if( $this->slot_has_next( $slot ) )
    {
      $new_index = $_SESSION['slot'][$slot]['stack']['index'] + 1;
      // update the stack index
      $_SESSION['slot'][$slot]['stack']['index'] = $new_index;
      // get the (now) next item
      $value = $this->slot_current( $slot );
      $this->update_slot_cookies();
    }

    return $value;
  }

  /**
   * Returns the widget currently being pointed to by the slot stack.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $slot The name of the slot.
   * @return array The name and arguments of the widget or NULL if the stack is empty.
   *               The associative array includes:
                   "name" => string,
                   "args" => associative array
   * @access public
   */
  public function slot_current( $slot )
  {
    // make sure the slot's stack has been created
    $this->validate_slot( $slot ); 
    // return the item at the current index
    $index = $_SESSION['slot'][$slot]['stack']['index'];
    return 0 <= $index ? $_SESSION['slot'][$slot]['stack']['widgets'][$index] : NULL;
  }

  /**
   * Resets the slot stacks to their initial state.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $slot The name of the slot.
   * @return string The name of the widget or NULL if the stack is empty.
   * @access public
   */
  public function slot_reset( $slot )
  {
    if( 'main' == $slot )
    { // by default, if there is no widget in the main slot then start with home
      $_SESSION['slot'][$slot]['stack']['index'] = 0;
      $_SESSION['slot'][$slot]['stack']['widgets'] =
        array( array( 'name' => 'self_home', 'args' => NULL ) );
    }
    else if( 'extruder' == $slot )
    {
      $_SESSION['slot'][$slot]['stack']['index'] = 0;
      $_SESSION['slot'][$slot]['stack']['widgets'] =
        array( array( 'name' => 'self_settings', 'args' => NULL ) );
    }
    else if( 'header' == $slot )
    {
      $_SESSION['slot'][$slot]['stack']['index'] = 0;
      $_SESSION['slot'][$slot]['stack']['widgets'] =
        array( array( 'name' => 'self_shortcuts', 'args' => NULL ) );
    }
    else
    {
      $_SESSION['slot'][$slot]['stack']['index'] = -1;
      $_SESSION['slot'][$slot]['stack']['widgets'] = array();
    }
  }

  /**
   * Makes sure that a stack exists for the given slot.
   * 
   * @author Patrick Emond <emondpd@mcamster.ca>
   * @access private
   */
  private function validate_slot( $slot )
  {
    if( !isset( $_SESSION['slot'][$slot] ) ) $this->slot_reset( $slot );
  }

  /**
   * Writes all slot stack information as cookies.
   * 
   * @author Patrick Emond <emondpd@mcamster.ca>
   * @access private
   */
  private function update_slot_cookies()
  {
    foreach( array_keys( $_SESSION['slot'] ) as $slot )
    {
      $widget = $this->slot_current( $slot );
      setcookie( "slot.$slot.widget", $widget['name'] );
      
      $index = $_SESSION['slot'][$slot]['stack']['index'];

      setcookie( "slot.$slot.prev", $this->slot_has_prev( $slot ) ?
        $_SESSION['slot'][$slot]['stack']['widgets'][$index-1]['name'] : NULL );

      setcookie( "slot.$slot.next", $this->slot_has_next( $slot ) ?
        $_SESSION['slot'][$slot]['stack']['widgets'][$index+1]['name'] : NULL );
    }
  }

  /**
   * Whether the session has been initialized
   * @var boolean
   * @access private
   */
  private $initialized = false;

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
