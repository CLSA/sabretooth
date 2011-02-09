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
 * user.view widget
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
    parent::__construct( 'user', $args );

    // define all template variables for this list
    $this->set_heading( sprintf( 'Viewing user "%s"', $this->record->name ) );
    $this->editable = true; // TODO: should be based on role
    $this->removable = true; // TODO: should be based on role
    
    // create an associative array with everything we want to display about the user
    $this->item = array( 'Username' => $this->record->name,
                         'Limesurvey username' => 'TODO' );

    // create the site sub-list widget
    $this->site_list = new site_list( $args );
    $this->site_list->set_parent( $this );
    $this->site_list->set_heading( 'User\'s site access list' );
    $this->site_list->set_checkable( false );
    $this->site_list->set_viewable( true );
    $this->site_list->set_editable( false );
    $this->site_list->set_removable( false );

    // create the activity sub-list widget
    $this->activity_list = new activity_list( $args );
    $this->activity_list->set_parent( $this );
    $this->activity_list->set_heading( 'User activity' );
    $this->activity_list->set_checkable( false );
    $this->activity_list->set_viewable( false );
    $this->activity_list->set_editable( false );
    $this->activity_list->set_removable( false );
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
    $this->activity_list->finish();

    // define all template variables for this widget
    $this->set_variable( 'id', $this->record->id );
    $this->set_variable( 'site_list', $this->site_list->get_variables() );
    $this->set_variable( 'activity_list', $this->activity_list->get_variables() );
  }
  
  /**
   * Overrides the site list widget's method.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return int
   * @access protected
   */
  public function determine_site_count()
  {
    return $this->record->get_site_count();
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
    return $this->record->get_site_list( $modifier );
  }

  /**
   * Overrides the activity list widget's method.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return int
   * @access protected
   */
  public function determine_activity_count()
  {
    return $this->record->get_activity_count();
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
    return $this->record->get_activity_list( $modifier );
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
