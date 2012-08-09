<?php
/**
 * session.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\business;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * Extends Cenozo's session class with custom functionality
 */
class session extends \cenozo\business\session
{
  /**
   * Initializes the session.
   * 
   * This method should be called immediately after initial construct of the session.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $site_name The name of a site to act under.  If null then a session
   *                variable will be used to determine the current site, or if not such session
   *               variable exists then a site which the user has access to will be selected
   *               automatically.
   * @param string $role_name The name of a role to act under.  If null then a session
   *               variable will be used to determine the current role, or if not such session
   *               variable exists then a role which the user has access to will be selected
   *               automatically.
   * @access public
   */
  public function initialize( $site_name = NULL, $role_name = NULL )
  {
    // don't initialize more than once
    if( $this->is_initialized() ) return;

    parent::initialize( $site_name, $role_name );

    // initialize the voip manager
    lib::create( 'business\voip_manager' )->initialize();
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
    // create the database if it doesn't exist yet
    if( is_null( $this->survey_database ) )
    {
      $setting_manager = lib::create( 'business\setting_manager' );
      $this->survey_database = lib::create( 'database\database',
        $setting_manager->get_setting( 'survey_db', 'driver' ),
        $setting_manager->get_setting( 'survey_db', 'server' ),
        $setting_manager->get_setting( 'survey_db', 'username' ),
        $setting_manager->get_setting( 'survey_db', 'password' ),
        $setting_manager->get_setting( 'survey_db', 'database' ),
        $setting_manager->get_setting( 'survey_db', 'prefix' ) );
    }

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
    // create the database if it doesn't exist yet
    if( is_null( $this->audit_database ) )
    {
      $setting_manager = lib::create( 'business\setting_manager' );
      if( $setting_manager->get_setting( 'audit_db', 'enabled' ) )
      {
        $this->audit_database = lib::create( 'database\database',
          $setting_manager->get_setting( 'audit_db', 'driver' ),
          $setting_manager->get_setting( 'audit_db', 'server' ),
          $setting_manager->get_setting( 'audit_db', 'username' ),
          $setting_manager->get_setting( 'audit_db', 'password' ),
          $setting_manager->get_setting( 'audit_db', 'database' ),
          $setting_manager->get_setting( 'audit_db', 'prefix' ) );
      }
    }

    return $this->audit_database;
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
      throw lib::create( 'exception\runtime',
        'Tried to get assignment for non-operator.', __METHOD__ );
    
    // query for assignments which do not have a end time
    $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'end_datetime', '=', NULL );
    $modifier->order_desc( 'start_datetime' );
    $assignment_list = $this->get_user()->get_assignment_list( $modifier );

    // only one assignment should ever be open at a time, warn if this isn't the case
    if( 1 < count( $assignment_list ) )
      log::crit(
        sprintf( 'Current operator (id: %d, name: %s), has more than one active assignment!',
                 $this->get_user()->id,
                 $this->get_user()->name ) );

    return 1 <= count( $assignment_list ) ? current( $assignment_list ) : NULL;
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
      throw lib::create( 'exception\runtime',
        'Tried to get phone call for non-operator.', __METHOD__ );
    
    // without an assignment there can be no current call
    $db_assignment = $this->get_current_assignment();
    if( is_null( $db_assignment) ) return NULL;

    // query for phone calls which do not have a end time
    $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'end_datetime', '=', NULL );
    $modifier->order_desc( 'start_datetime' );
    $phone_call_list = $db_assignment->get_phone_call_list( $modifier );

    // only one phone call should ever be open at a time, warn if this isn't the case
    if( 1 < count( $phone_call_list ) )
      log::crit(
        sprintf( 'Current operator (id: %d, name: %s), has more than one active phone call!',
                 $this->get_user()->id,
                 $this->get_user()->name ) );

    return 1 <= count( $phone_call_list ) ? current( $phone_call_list ) : NULL;
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
    $setting_manager = lib::create( 'business\setting_manager' );
    $voip_manager = lib::create( 'business\voip_manager' );
    if( !$setting_manager->get_setting( 'voip', 'enabled' ) )
    { // if voip is not enabled then allow calls
      $allow = true;
    }
    else if( $voip_manager->get_sip_enabled() )
    { // voip is enabled, so make sure sip is also enabled
      $allow = true;
    }
    else
    { // check to see if we can call without a SIP connection
      $allow = $setting_manager->get_setting( 'voip', 'survey without sip' );
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
    $activity = lib::create( 'database\activity' );
    $activity->user_id = $this->get_user()->id;
    $activity->site_id = $this->get_site()->id;
    $activity->role_id = $this->get_role()->id;
    $activity->operation_id = $operation->get_id();
    $activity->query = serialize( $args );
    $activity->elapsed = util::get_elapsed_time();
    $activity->datetime = util::get_datetime_object()->format( 'Y-m-d H:i:s' );
    $activity->save();
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

      // mangle $slot so that the parent method doesn't change what we've done above
      $slot = 'main has already been processed';
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

    // kill the rescore and secondary contact cookies in case they exist
    setcookie( 'rescoring_interview', NULL, time() - 3600, COOKIE_PATH );
    setcookie( 'secondary_id', NULL, time() - 3600, COOKIE_PATH );
    setcookie( 'secondary_participant_id', NULL, time() - 3600, COOKIE_PATH );
    setcookie( 'secondary_participant_first_name', NULL, time() - 3600, COOKIE_PATH );
    setcookie( 'secondary_participant_last_name', NULL, time() - 3600, COOKIE_PATH );

    $this->update_slot_cookies();
  }
  
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
}
?>
