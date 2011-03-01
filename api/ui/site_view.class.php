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
    $this->item['name'] =
      array( 'heading' => 'Name',
             'type' => 'string',
             'value' => $this->get_record()->name );
    $this->item['users'] =
      array( 'heading' => 'Number of users',
             'type' => 'constant',
             // add a space to get around a bug in twig
             'value' => ' '.$this->get_record()->get_user_count() );

    $db_activity = $this->get_record()->get_last_activity();
    $last = \sabretooth\util::get_fuzzy_time_ago(
              is_null( $db_activity ) ? null : $db_activity->date );
    $this->item['last_activity'] =
      array( 'heading' => 'Last activity',
             'type' => 'constant',
             'value' => $last );

    // create the access sub-list widget
    $this->access_list = new access_list( $args );
    $this->access_list->set_parent( $this );
    $this->access_list->set_heading( 'This site\'s access list' );

    // create the activity sub-list widget
    $this->activity_list = new activity_list( $args );
    $this->activity_list->set_parent( $this );
    $this->activity_list->set_heading( 'Site activity' );
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

    $this->access_list->finish();
    $this->set_variable( 'access_list', $this->access_list->get_variables() );

    $this->activity_list->finish();
    $this->set_variable( 'activity_list', $this->activity_list->get_variables() );
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
    if( NULL == $modifier ) $modifier = new \sabretooth\database\modifier();
    $modifier->where( 'site_id', $this->get_record()->id );
    return \sabretooth\database\access::count( $modifier );
  }

  /**
   * Overrides the access list widget's method to only include this site's access.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the list.
   * @return array( active_record )
   * @access protected
   */
  public function determine_access_list( $modifier = NULL )
  {
    if( NULL == $modifier ) $modifier = new \sabretooth\database\modifier();
    $modifier->where( 'site_id', $this->get_record()->id );
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
