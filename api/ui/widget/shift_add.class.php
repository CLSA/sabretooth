<?php
/**
 * shift_add.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui\widget;
use sabretooth\log, sabretooth\util;
use sabretooth\business as bus;
use sabretooth\database as db;
use sabretooth\exception as exc;

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
    $this->show_heading( false );
    
    // check for initial values
    $this->date = $this->get_argument( 'date', NULL );
    $this->start_time = $this->get_argument( 'start_time', NULL );
    $this->end_time = $this->get_argument( 'end_time', NULL );

    // add items to the view
    $this->add_item( 'site_id', 'hidden' );
    $this->add_item( 'date', 'date', 'Date' );
    $this->add_item( 'start_time', 'time', 'Start Time' );
    $this->add_item( 'end_time', 'time', 'End Time' );

    try
    {
      // and a list of users
      $this->user_list = new user_list( $args );
      $this->user_list->set_parent( $this, 'edit' );
      $this->user_list->set_heading( 'Choose users to add to this shift' );
    }
    catch( exc\permission $e )
    {
      $this->user_list = NULL;
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

    if( $this->parent && 'user' == $this->parent->get_subject() )
    {
      $this->set_variable( 'user_id', $this->parent->get_record()->id );
    }
    else if( !is_null( $this->user_list ) )
    {
      $this->user_list->finish();
      $this->set_variable( 'user_list', $this->user_list->get_variables() );
    }

    // set the view's items
    $this->set_item( 'site_id', bus\session::self()->get_site()->id, true );
    $this->set_item( 'date', $this->date, true );
    $this->set_item( 'start_time', $this->start_time, true );
    $this->set_item( 'end_time', $this->end_time, true );
    
    $this->finish_setting_items();
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
    $db_role = db\role::get_unique_record( 'name', 'operator' );
    if( is_null( $modifier ) ) $modifier = new db\modifier();
    $modifier->where( 'role_id', '=', $db_role->id );
    $modifier->where( 'site_id', '=', bus\session::self()->get_site()->id );

    return db\user::count( $modifier );
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
    $db_role = db\role::get_unique_record( 'name', 'operator' );
    if( is_null( $modifier ) ) $modifier = new db\modifier();
    $modifier->where( 'role_id', '=', $db_role->id );
    $modifier->where( 'site_id', '=', bus\session::self()->get_site()->id );

    return db\user::select( $modifier );
  }

  /**
   * The initial date.
   * @var string
   * @access public
   */
  public $date = '';

  /**
   * The initial start time.
   * @var string
   * @access public
   */
  public $start_time = '';

  /**
   * The initial end time.
   * @var string
   * @access public
   */
  public $end_time = '';

  /**
   * The user list widget used to define the access type.
   * @var user_list
   * @access protected
   */
  protected $user_list = NULL;
}
?>
