<?php
/**
 * session.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\business
 * @filesource
 */

namespace sabretooth\business;
use sabretooth\log, sabretooth\util;
use sabretooth\database as db;
use sabretooth\exception as exc;

/**
 * session: handles all session-based information
 *
 * The session class is used to track all information from the time a user logs into the system
 * until they log out.
 * This class is a singleton, instead of using the new operator call {@singleton() 
 * @package sabretooth\business
 */
final class session extends \sabretooth\singleton
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
    
    // the first argument is the settings array from an .ini file
    $setting_manager = setting_manager::self( $arguments[0] );
    
    // set error reporting
    error_reporting(
      $setting_manager->get_setting( 'general', 'development_mode' ) ? E_ALL | E_STRICT : E_ALL );

    // setup the session variables
    if( !isset( $_SESSION['slot'] ) ) $_SESSION['slot'] = array();
  }
  
  /**
   * Initializes the session.
   * 
   * This method should be called immediately after initial construct of the session.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @throws exception\permission
   * @access public
   */
  public function initialize()
  {
    // don't initialize more than once
    if( $this->initialized ) return;

    $setting_manager = setting_manager::self();

    // create the databases
    $this->database = new db\database(
      $setting_manager->get_setting( 'db', 'driver' ),
      $setting_manager->get_setting( 'db', 'server' ),
      $setting_manager->get_setting( 'db', 'username' ),
      $setting_manager->get_setting( 'db', 'password' ),
      $setting_manager->get_setting( 'db', 'database' ),
      $setting_manager->get_setting( 'db', 'prefix' ) );
    $this->survey_database = new db\database(
      $setting_manager->get_setting( 'survey_db', 'driver' ),
      $setting_manager->get_setting( 'survey_db', 'server' ),
      $setting_manager->get_setting( 'survey_db', 'username' ),
      $setting_manager->get_setting( 'survey_db', 'password' ),
      $setting_manager->get_setting( 'survey_db', 'database' ),
      $setting_manager->get_setting( 'survey_db', 'prefix' ) );
    if( $setting_manager->get_setting( 'audit_db', 'enabled' ) )
    {
      // If not set then the audit database settings use the same as limesurvey,
      // with the exception of the prefix
      $this->audit_database = new db\database(
        $setting_manager->get_setting( 'audit_db', 'driver' ),
        $setting_manager->get_setting( 'audit_db', 'server' ),
        $setting_manager->get_setting( 'audit_db', 'username' ),
        $setting_manager->get_setting( 'audit_db', 'password' ),
        $setting_manager->get_setting( 'audit_db', 'database' ),
        $setting_manager->get_setting( 'audit_db', 'prefix' ) );
    }

    // determine the user (setting the user will also set the site and role)
    $user_name = $_SERVER[ 'PHP_AUTH_USER' ];
    $this->set_user( db\user::get_unique_record( 'name', $user_name ) );
    if( NULL == $this->user )
      throw new exc\permission(
        db\operation::get_operation( 'push', 'self', 'set_role' ), __METHOD__ );

    $this->initialized = true;
  }
  
  /**
   * Get the main database.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return database
   * @access public
   */
  public function get_database()
  {
    return $this->database;
  }

  /**
   * Get the survey database.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return database
   * @access public
   */
  public function get_survey_database()
  {
    return $this->survey_database;
  }

  /**
   * Get the audit database.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return database
   * @access public
   */
  public function get_audit_database()
  {
    return $this->audit_database;
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
    if( is_null( $db_site ) || is_null( $db_role ) )
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

        if( !isset( $_SESSION['current_site_id'] ) ||
            $_SESSION['current_site_id'] != $this->site->id ||
            !isset( $_SESSION['current_role_id'] ) ||
            $_SESSION['current_role_id'] != $this->role->id )
        {
          // clean out the slot stacks
          foreach( array_keys( $_SESSION['slot'] ) as $slot ) $this->slot_reset( $slot );
          $_SESSION['current_site_id'] = $this->site->id;
          $_SESSION['current_role_id'] = $this->role->id;
        }
      }
      else throw new exc\permission(
        db\operation::get_operation( 'push', 'self', 'set_role' ), __METHOD__ );
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
    if( is_null( $this->user ) )
    {
      $this->set_site_and_role( NULL, NULL );
    }
    else if( !$this->user->active )
    {
      throw new exc\notice(
        'Your account has been deactivated.<br>'.
        'Please contact a supervisor to regain access to the system.', __METHOD__ );
    }
    else
    {
      // do not use set functions or we will loose cookies
      $this->site = NULL;
      $this->role = NULL;

      // see if we already have the current site stored in the php session
      if( isset( $_SESSION['current_site_id'] ) && isset( $_SESSION['current_role_id'] ) )
      {
        $this->set_site_and_role( new db\site( $_SESSION['current_site_id'] ),
                                  new db\role( $_SESSION['current_role_id'] ) );
      }
      
      // we still don't have a site and role, we need to pick them
      if( is_null( $this->site ) || is_null( $this->role ) )
      {
        $db_site = NULL;
        $db_role = NULL;

        $site_list = $this->user->get_site_list();
        if( 0 == count( $site_list ) )
          throw new exc\notice(
            'Your account does not have access to any site.<br>'.
            'Please contact a supervisor to be granted access to a site.', __METHOD__ );
        
        // if the user has logged in before, use whatever site/role they last used
        $activity_mod = new db\modifier();
        $activity_mod->where( 'user_id', '=', $this->user->id );
        $activity_mod->order_desc( 'datetime' );
        $activity_mod->limit( 1 );
        $db_activity = current( db\activity::select( $activity_mod ) );
        if( $db_activity )
        {
          // make sure the user still has access to the site/role
          $role_mod = new db\modifier();
          $role_mod->where( 'site_id', '=', $db_activity->site_id );
          $role_mod->where( 'role_id', '=', $db_activity->role_id );
          $db_role = current( $this->user->get_role_list( $role_mod ) );
          
          // only bother setting the site if the access exists
          if( $db_role ) $db_site = new db\site( $db_activity->site_id );
        }

        // if we still don't have a site/role then load the first one we can find
        if( !$db_role || !$db_site ) 
        {
          $db_site = current( $site_list );
          $role_mod = new db\modifier();
          $role_mod->where( 'site_id', '=', $db_site->id );
          $db_role = current( $this->user->get_role_list( $role_mod ) );
        }

        $this->set_site_and_role( $db_site, $db_role );
      }
    }
  }
  
  /**
   * Return whether the session has permission to perform the given operation.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\operation $operation If null this method returns false.
   * @return boolean
   * @access public
   */
  public function is_allowed( $operation )
  {
    return !is_null( $operation ) && !is_null( $this->role ) &&
           ( !$operation->restricted || $this->role->has_operation( $operation ) );
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
    $theme = setting_manager::self()->get_setting( 'interface', 'default_theme' );

    if( !is_null( $this->user ) )
    {
      $user_theme = $this->user->theme;
      if( !is_null( $user_theme ) ) $theme = $user_theme;
    }

    return $theme;
  }
  
  /**
   * Get the user's current assignment.
   * Should only be called if the user is an operator, otherwise an exception will be thrown.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return database\assignment
   * @throws exception\runtime
   * @access public
   */
  public function get_current_assignment()
  {
    // make sure the user is an operator
    if( 'operator' != $this->get_role()->name )
      throw new exc\runtime( 'Tried to get assignment for non-operator.', __METHOD__ );
    
    // query for assignments which do not have a end time
    $modifier = new db\modifier();
    $modifier->where( 'end_datetime', '=', NULL );
    $assignment_list = $this->get_user()->get_assignment_list( $modifier );

    // only one assignment should ever be open at a time, warn if this isn't the case
    if( 1 < count( $assignment_list ) )
      log::crit(
        sprintf( 'Current operator (id: %d, name: %s), has more than one active assignment!',
                 $this->get_user()->id,
                 $this->get_user()->name ) );

    return 1 == count( $assignment_list ) ? current( $assignment_list ) : NULL;
  }

  /**
   * Get the user's current phone call.
   * Should only be called if the user is an operator, otherwise an exception will be thrown.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return database\phone_call
   * @throws exception\runtime
   * @access public
   */
  public function get_current_phone_call()
  {
    // make sure the user is an operator
    if( 'operator' != $this->get_role()->name )
      throw new exc\runtime( 'Tried to get phone call for non-operator.', __METHOD__ );
    
    // without an assignment there can be no current call
    $db_assignment = $this->get_current_assignment();
    if( is_null( $db_assignment) ) return NULL;

    // query for phone calls which do not have a end time
    $modifier = new db\modifier();
    $modifier->where( 'end_datetime', '=', NULL );
    $phone_call_list = $db_assignment->get_phone_call_list( $modifier );

    // only one phone call should ever be open at a time, warn if this isn't the case
    if( 1 < count( $phone_call_list ) )
      log::crit(
        sprintf( 'Current operator (id: %d, name: %s), has more than one active phone call!',
                 $this->get_user()->id,
                 $this->get_user()->name ) );

    return 1 == count( $phone_call_list ) ? current( $phone_call_list ) : NULL;
  }

  /**
   * Determines whether the user is allowed to make calls.  This depends on whether a SIP
   * is detected and whether or not operators are allowed to make calls without using VoIP
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function get_allow_call()
  {
    $allow = false;
    if( !setting_manager::self()->get_setting( 'voip', 'enabled' ) )
    { // if voip is not enabled then allow calls
      $allow = true;
    }
    else if( voip_manager::self()->get_sip_enabled() )
    { // voip is enabled, so make sure sip is also enabled
      $allow = true;
    }
    else
    { // check to see if we can call without a SIP connection
      $allow = setting_manager::self()->get_setting( 'voip', 'survey without sip' );
    }

    return $allow;
  }

  /**
   * Add an operation to this user's activity log.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param ui\operation $operation The operation to log.
   * @param array $args The arguments passed to the operation.
   * @access public
   */
  public function log_activity( $operation, $args )
  {
    // add the operation as activity
    $activity = new db\activity();
    $activity->user_id = $this->user->id;
    $activity->site_id = $this->site->id;
    $activity->role_id = $this->role->id;
    $activity->operation_id = $operation->get_id();
    $activity->query = serialize( $args );
    $activity->elapsed = util::get_elapsed_time();
    $activity->datetime = util::get_datetime_object()->format( 'Y-m-d H:i:s' );
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
      $_SESSION['slot'][$slot]['stack']['widgets'] =
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
      $_SESSION['slot'][$slot]['stack']['widgets'][] = array( 'name' => $name, 'args' => $args );
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
    {
      if( 'operator' == $this->get_role()->name )
      { // operators always start at the assignment widget
        $_SESSION['slot'][$slot]['stack']['index'] = 0;
        $_SESSION['slot'][$slot]['stack']['widgets'] =
          array( array( 'name' => 'operator_assignment', 'args' => NULL ) );
      }
      else
      { // by default, if there is no widget in the main slot then start with home
        $_SESSION['slot'][$slot]['stack']['index'] = 0;
        $_SESSION['slot'][$slot]['stack']['widgets'] =
          array( array( 'name' => 'self_home', 'args' => NULL ) );
      }
    }
    else if( 'settings' == $slot )
    {
      $_SESSION['slot'][$slot]['stack']['index'] = 0;
      $_SESSION['slot'][$slot]['stack']['widgets'] =
        array( array( 'name' => 'self_settings', 'args' => NULL ) );
    }
    else if( 'menu' == $slot )
    {
      $_SESSION['slot'][$slot]['stack']['index'] = 0;
      $_SESSION['slot'][$slot]['stack']['widgets'] =
        array( array( 'name' => 'self_menu', 'args' => NULL ) );
    }
    else if( 'status' == $slot )
    {
      $_SESSION['slot'][$slot]['stack']['index'] = 0;
      $_SESSION['slot'][$slot]['stack']['widgets'] =
        array( array( 'name' => 'self_status', 'args' => NULL ) );
    }
    else if( 'shortcuts' == $slot )
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
      setcookie( 'slot__'.$slot.'__widget', $widget['name'], 0, COOKIE_PATH );
      
      $index = $_SESSION['slot'][$slot]['stack']['index'];

      setcookie( 'slot__'.$slot.'__prev', $this->slot_has_prev( $slot ) ?
        $_SESSION['slot'][$slot]['stack']['widgets'][$index-1]['name'] : NULL, 0, COOKIE_PATH );

      setcookie( 'slot__'.$slot.'__next', $this->slot_has_next( $slot ) ?
        $_SESSION['slot'][$slot]['stack']['widgets'][$index+1]['name'] : NULL, 0, COOKIE_PATH );
    }
  }
  
  /**
   * Gets the current survey state.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return boolean
   * @access public
   */
  public function get_survey_url()
  {
    // only operators can fill out surveys
    if( 'operator' != $this->role->name ) return false;
    
    // must have an assignment
    $db_assignment = $this->get_current_assignment();
    if( is_null( $db_assignment ) ) return false;
    
    // the assignment must have an open call
    $modifier = new db\modifier();
    $modifier->where( 'end_datetime', '=', NULL );
    $call_list = $db_assignment->get_phone_call_list( $modifier );
    if( 0 == count( $call_list ) ) return false;

    // determine the current sid and token
    $sid = $db_assignment->get_current_sid();
    $token = $db_assignment->get_current_token();
    if( false === $sid || false == $token ) return false;
    
    // determine which language to use
    $lang = $db_assignment->get_interview()->get_participant()->language;
    if( !$lang ) $lang = 'en';
    
    return LIMESURVEY_URL.sprintf( '/index.php?sid=%s&lang=%s&token=%s', $sid, $lang, $token );
  }

  /**
   * Returns whether the session has been initialized or not.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return boolean
   * @access public
   */
  public function is_initialized()
  {
    return $this->initialized;
  }

  /**
   * Whether the session has been initialized
   * @var boolean
   * @access private
   */
  private $initialized = false;

  /**
   * The main database object.
   * @var database
   * @access private
   */
  private $database = NULL;

  /**
   * The survey database object.
   * @var database
   * @access private
   */
  private $survey_database = NULL;

  /**
   * The survey database object.
   * @var database
   * @access private
   */
  private $audit_database = NULL;

  /**
   * The record of the current user.
   * @var database\user
   * @access private
   */
  private $user = NULL;

  /**
   * The record of the current role.
   * @var database\role
   * @access private
   */
  private $role = NULL;

  /**
   * The record of the current site.
   * @var database\site
   * @access private
   */
  private $site = NULL;
}
?>
