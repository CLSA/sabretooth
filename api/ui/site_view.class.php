<?php
/**
 * site_view.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui;

/**
 * site.view widget
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
    parent::__construct( 'site', $args );

    // define all template variables for this list
    $this->heading = 'Viewing site "'.$this->record->name.'"';
    $this->editable = true; // TODO: should be based on role
    $this->removable = false;
    
    // create an associative array with everything we want to display about the site
    $this->item = array( 'Name' => $this->record->name,
                         'Users' => $this->record->get_user_count(),
                         'Last activity' => $this->record->get_last_activity()->date );

    // create the user sub-list widget
    $this->user_list = new user_list( $args );
    $this->user_list->set_parent( $this );
    $this->user_list->set_heading( 'Users belonging to this site' );
    $this->user_list->set_checkable( false );
    $this->user_list->set_viewable( true );
    $this->user_list->set_editable( false );
    $this->user_list->set_removable( false );

    // create the activity sub-list widget
    $this->activity_list = new activity_list( $args );
    $this->activity_list->set_parent( $this );
    $this->activity_list->set_heading( 'Site activity' );
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
    $this->user_list->finish();
    $this->activity_list->finish();

    // define all template variables for this widget
    $this->set_variable( 'id', $this->record->id );
    $this->set_variable( 'user_list', $this->user_list->get_variables() );
    $this->set_variable( 'activity_list', $this->activity_list->get_variables() );
  }

  /**
   * Overrides the user list widget's method.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return int
   * @access protected
   */
  public function determine_user_count()
  {
    return $this->record->get_user_count();
  }

  /**
   * Overrides the user list widget's method.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param int $count The number of rows to include.
   * @param int $offset The offset to start rows at.
   * @param string $sort The column to sort the list by.
   * @param boolean $desc Whether to sort in descending or ascending order.
   * @return array( active_record )
   * @access protected
   */
  public function determine_user_list( $count, $offset, $column, $desc )
  {
    return $this->record->get_user_list( $count, $offset, $column, $desc );
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
   * @param int $count The number of rows to include.
   * @param int $offset The offset to start rows at.
   * @param string $sort The column to sort the list by.
   * @param boolean $desc Whether to sort in descending or ascending order.
   * @return array( active_record )
   * @access protected
   */
  public function determine_activity_list( $count, $offset, $column, $desc )
  {
    return $this->record->get_activity_list( $count, $offset, $column, $desc );
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
