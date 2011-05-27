<?php
/**
 * site_view.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui;
use sabretooth\log, sabretooth\util;
use sabretooth\business as bus;
use sabretooth\database as db;
use sabretooth\exception as exc;

/**
 * widget site view
 * 
 * @package sabretooth\ui
 */
class site_view extends base_view
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
    parent::__construct( 'site', 'view', $args );
    
    // create an associative array with everything we want to display about the site
    $this->add_item( 'name', 'string', 'Name' );
    $this->add_item( 'timezone', 'enum', 'Time Zone' );
    $this->add_item( 'operators_expected', 'number', 'Minimum expected operators' );
    $this->add_item( 'users', 'constant', 'Number of users' );
    $this->add_item( 'last_activity', 'constant', 'Last activity' );

    try
    {
      // create the access sub-list widget
      $this->access_list = new access_list( $args );
      $this->access_list->set_parent( $this );
      $this->access_list->set_heading( 'Site access list' );
    }
    catch( exc\permission $e )
    {
      $this->access_list = NULL;
    }

    try
    {
      // create the activity sub-list widget
      $this->activity_list = new activity_list( $args );
      $this->activity_list->set_parent( $this );
      $this->activity_list->set_heading( 'Site activity' );
    }
    catch( exc\permission $e )
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
    
    // create enum arrays
    $timezones = db\site::get_enum_values( 'timezone' );
    $timezones = array_combine( $timezones, $timezones );

    // set the view's items
    $this->set_item( 'name', $this->get_record()->name, true );
    $this->set_item( 'timezone', $this->get_record()->timezone, true, $timezones );
    $this->set_item( 'operators_expected', $this->get_record()->operators_expected, true );
    $this->set_item( 'users', $this->get_record()->get_user_count() );

    $db_activity = $this->get_record()->get_last_activity();
    $last = util::get_fuzzy_period_ago(
              is_null( $db_activity ) ? null : $db_activity->datetime );
    $this->set_item( 'last_activity', $last );

    $this->finish_setting_items();

    // finish the child widgets
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
   * Overrides the access list widget's method to only include this site's access.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the list.
   * @return int
   * @access protected
   */
  public function determine_access_count( $modifier = NULL )
  {
    if( NULL == $modifier ) $modifier = new db\modifier();
    $modifier->where( 'site_id', '=', $this->get_record()->id );
    return db\access::count( $modifier );
  }

  /**
   * Overrides the access list widget's method to only include this site's access.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the list.
   * @return array( record )
   * @access protected
   */
  public function determine_access_list( $modifier = NULL )
  {
    if( NULL == $modifier ) $modifier = new db\modifier();
    $modifier->where( 'site_id', '=', $this->get_record()->id );
    return db\access::select( $modifier );
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
    return $this->get_record()->get_activity_list( $modifier );
  }

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
