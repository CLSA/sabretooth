<?php
/**
 * shift_add.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui;

/**
 * widget shift add
 * 
 * @package sabretooth\ui
 */
class shift_add extends base_view
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
    parent::__construct( 'shift', 'add', $args );
    
    // add items to the view
    $this->add_item( 'date', 'date', 'Date' );
    $this->add_item( 'start_time', 'time', 'Start Time' );
    $this->add_item( 'end_time', 'time', 'End Time' );

    // and a list of sites
    $this->site_list = new site_list( $args );
    $this->site_list->set_parent( $this, 'edit' );
    $this->site_list->set_heading( 'Choose sites to add to this shift' );

    // and a list of users
    $this->user_list = new user_list( $args );
    $this->user_list->set_parent( $this, 'edit' );
    $this->user_list->set_heading( 'Choose users to add to this shift' );
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
    
    if( 'site' != $this->parent->get_subject() )
    {
      $this->site_list->finish();
      $this->set_variable( 'site_list', $this->site_list->get_variables() );
    }
    
    if( 'user' != $this->parent->get_subject() )
    {
      $this->user_list->finish();
      $this->set_variable( 'user_list', $this->user_list->get_variables() );
    }

    // this widget must have a parent, and it's subject must be a user or site
    // TODO: remove this, and the error code
    /*
    if( is_null( $this->parent ) ||
        ( 'user' != $this->parent->get_subject() && 'site' != $this->parent->get_subject() ) )
      throw new \sabretooth\exception\runtime(
        'Consent widget must have a parent with either user or site as the subject.', __METHOD__ );
    */

    // set the view's items
    $this->set_item( 'date', '', true );
    $this->set_item( 'start_time', '', true );
    $this->set_item( 'end_time', '', true );
    
    $this->finish_setting_items();
  }

  /**
   * Overrides the site list widget's method.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the list.
   * @return int
   * @access protected
   */
  public function determine_site_count( $modifier = NULL )
  {
    // TODO: only include sites the user has operator access to
    return \sabretooth\database\site::count( $modifier );
  }

  /**
   * Overrides the site list widget's method.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the list.
   * @return array( record )
   * @access protected
   */
  public function determine_site_list( $modifier = NULL )
  {
    // TODO: only include sites the user has operator access to
    return \sabretooth\database\site::select( $modifier );
  }

  /**
   * Overrides the user list widget's method.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the list.
   * @return int
   * @access protected
   */
  public function determine_user_count( $modifier = NULL )
  {
    // TODO: only include operators
    return \sabretooth\database\user::count( $modifier );
  }

  /**
   * Overrides the user list widget's method.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the list.
   * @return array( record )
   * @access protected
   */
  public function determine_user_list( $modifier = NULL )
  {
    // TODO: only include operators
    return \sabretooth\database\user::select( $modifier );
  }

  /**
   * The site list widget used to define the access type.
   * @var site_list
   * @access protected
   */
  protected $site_list = NULL;

  /**
   * The user list widget used to define the access type.
   * @var user_list
   * @access protected
   */
  protected $user_list = NULL;
}
?>
