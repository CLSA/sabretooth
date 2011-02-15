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
class user_view extends base_record
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
    $this->item['name'] =
      array( 'heading' => 'Username',
             'type' => 'string',
             'value' => $this->get_record()->name );
    $this->item['active'] =
      array( 'heading' => 'Active',
             'type' => 'boolean',
             'value' => $this->get_record()->active ? 'Yes' : 'No' );
    $this->item['limesurvey_username'] =
      array( 'heading' => 'Limesurvey username',
             'type' => 'constant',
             'value' => 'TODO' );
    
    $db_activity = $this->get_record()->get_last_activity();
    $last = \sabretooth\util::get_fuzzy_time_ago(
              is_null( $db_activity ) ? null : $db_activity->date );
    $this->item['last_activity'] =
      array( 'heading' => 'Last activity',
             'type' => 'constant',
             'value' => $last );

    // create the site sub-list widget
    $this->site_list = new site_list( $args );
    $this->site_list->set_parent( $this );
    $this->site_list->set_heading( 'User\'s site access list' );

    // create the activity sub-list widget
    $this->activity_list = new activity_list( $args );
    $this->activity_list->set_parent( $this );
    $this->activity_list->set_heading( 'User activity' );
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

    $this->site_list->finish();
    $this->set_variable( 'site_list', $this->site_list->get_variables() );

    $this->activity_list->finish();
    $this->set_variable( 'activity_list', $this->activity_list->get_variables() );
  }
  
  /**
   * Overrides the site list widget's method.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the list.
   * @return int
   * @access protected
   */
  public function determine_site_count( $modifier )
  {
    return $this->get_record()->get_site_count( $modifier );
  }

  /**
   * Overrides the site list widget's method.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the list.
   * @return array( active_record )
   * @access protected
   */
  public function determine_site_list( $modifier )
  {
    return $this->get_record()->get_site_list( $modifier );
  }

  /**
   * Overrides the activity list widget's method.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return int
   * @access protected
   */
  public function determine_activity_count( $modifier )
  {
    return $this->get_record()->get_activity_count( $modifier );
  }

  /**
   * Overrides the activity list widget's method.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the list.
   * @return array( active_record )
   * @access protected
   */
  public function determine_activity_list( $modifier )
  {
    return $this->get_record()->get_activity_list( $modifier );
  }
  /**
   * The user list widget.
   * @var user_list
   * @access protected
   */
  protected $user_list = NULL;

  /**
   * The activity list widget.
   * @var activity_list
   * @access protected
   */
  protected $activity_list = NULL;
}
?>
