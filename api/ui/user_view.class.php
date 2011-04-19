<?php
/**
 * user_view.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui;

/**
 * widget user view
 * 
 * @package sabretooth\ui
 */
class user_view extends base_view
{
  /**
   * Constructor
   * 
   * Defines all variables which need to be set for the associated template.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'user', 'view', $args );

    // create an associative array with everything we want to display about the user
    $this->add_item( 'name', 'string', 'Username' );
    $this->add_item( 'first_name', 'string', 'First name' );
    $this->add_item( 'last_name', 'string', 'Last name' );
    $this->add_item( 'active', 'boolean', 'Active' );
    $this->add_item( 'last_activity', 'constant', 'Last activity' );
    
    try
    {
      // create the shift sub-list widget
      $this->shift_list = new shift_list( $args );
      $this->shift_list->set_parent( $this );
      $this->shift_list->set_heading( 'User\'s shift list' );
    }
    catch( \sabretooth\exception\permission $e )
    {
      $this->shift_list = NULL;
    }

    try
    {
      // create the access sub-list widget
      $this->access_list = new access_list( $args );
      $this->access_list->set_parent( $this );
      $this->access_list->set_heading( 'User\'s site access list' );
    }
    catch( \sabretooth\exception\permission $e )
    {
      $this->access_list = NULL;
    }

    try
    {
      // create the activity sub-list widget
      $this->activity_list = new activity_list( $args );
      $this->activity_list->set_parent( $this );
      $this->activity_list->set_heading( 'User activity' );
    }
    catch( \sabretooth\exception\permission $e )
    {
      $this->activity_list = NULL;
    }
  }

  /**
   * Finish setting the variables in a widget.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function finish()
  {
    parent::finish();

    // set the view's items
    $this->set_item( 'name', $this->get_record()->name, true );
    $this->set_item( 'first_name', $this->get_record()->first_name, true );
    $this->set_item( 'last_name', $this->get_record()->last_name, true );
    $this->set_item( 'active', $this->get_record()->active, true );
    
    $db_activity = $this->get_record()->get_last_activity();
    $last = \sabretooth\util::get_fuzzy_period_ago(
              is_null( $db_activity ) ? null : $db_activity->date );
    $this->set_item( 'last_activity', $last );

    $this->finish_setting_items();
    
    if( !is_null( $this->shift_list ) )
    {
      // determine if the user has the operator role at any sites
      $db_role = \sabretooth\database\role::get_unique_record( 'name', 'operator' );
      $modifier = new \sabretooth\database\modifier();
      $modifier->where( 'user_id', '=', $this->get_record()->id );
      $modifier->where( 'role_id', '=', $db_role->id );
      $is_operator = 0 < count( \sabretooth\database\access::select( $modifier ) );
  
      // finish the child widgets
      if( $is_operator )
      { // only add the shift list if the user has an operator role
        $this->shift_list->finish();
        $this->set_variable( 'shift_list', $this->shift_list->get_variables() );
      }
    }

    if( !is_null( $this->access_list ) )
    {
      $this->access_list->finish();
      $this->set_variable( 'access_list', $this->access_list->get_variables() );
    }

    if( !is_null( $this->activity_list ) )
    {
      $this->activity_list->finish();
      $this->set_variable( 'activity_list', $this->activity_list->get_variables() );
    }
  }
  
  /**
   * Overrides the access list widget's method.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the list.
   * @return int
   * @access protected
   */
  public function determine_access_count( $modifier = NULL )
  {
    if( NULL == $modifier ) $modifier = new \sabretooth\database\modifier();
    $modifier->where( 'user_id', '=', $this->get_record()->id );
    if( !site_restricted_list::may_restrict() )
      $modifier->where( 'site_id', '=', \sabretooth\business\session::self()->get_site()->id );
    return \sabretooth\database\access::count( $modifier );
  }

  /**
   * Overrides the access list widget's method.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the list.
   * @return array( record )
   * @access protected
   */
  public function determine_access_list( $modifier = NULL )
  {
    if( NULL == $modifier ) $modifier = new \sabretooth\database\modifier();
    $modifier->where( 'user_id', '=', $this->get_record()->id );
    if( !site_restricted_list::may_restrict() )
      $modifier->where( 'site_id', '=', \sabretooth\business\session::self()->get_site()->id );
    return \sabretooth\database\access::select( $modifier );
  }

  /**
   * Overrides the activity list widget's method.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return int
   * @access protected
   */
  public function determine_activity_count( $modifier = NULL )
  {
    if( !site_restricted_list::may_restrict() )
    {
      if( NULL == $modifier ) $modifier = new \sabretooth\database\modifier();
      $modifier->where( 'site_id', '=', \sabretooth\business\session::self()->get_site()->id );
    }

    return $this->get_record()->get_activity_count( $modifier );
  }

  /**
   * Overrides the activity list widget's method.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the list.
   * @return array( record )
   * @access protected
   */
  public function determine_activity_list( $modifier = NULL )
  {
    if( !site_restricted_list::may_restrict() )
    {
      if( NULL == $modifier ) $modifier = new \sabretooth\database\modifier();
      $modifier->where( 'site_id', '=', \sabretooth\business\session::self()->get_site()->id );
    }

    return $this->get_record()->get_activity_list( $modifier );
  }

  /**
   * The shift list widget.
   * @var shift_list
   * @shift protected
   */
  protected $shift_list = NULL;

  /**
   * The access list widget.
   * @var access_list
   * @access protected
   */
  protected $access_list = NULL;

  /**
   * The activity list widget.
   * @var activity_list
   * @access protected
   */
  protected $activity_list = NULL;
}
?>
