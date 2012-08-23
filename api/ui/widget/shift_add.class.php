<?php
/**
 * shift_add.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui\widget;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * widget shift add
 */
class shift_add extends \cenozo\ui\widget\base_view
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
  }

  /**
   * Processes arguments, preparing them for the operation.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @throws exception\notice
   * @access protected
   */
  protected function prepare()
  {
    parent::prepare();
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

    // and a list of users
    $this->user_list = lib::create( 'ui\widget\user_list', $this->arguments );
    $this->user_list->set_parent( $this );
    $this->user_list->set_checkable( true );
    $this->user_list->set_heading( 'Choose users to add to this shift' );
  }

  /**
   * Finish setting the variables in a widget.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access protected
   */
  protected function setup()
  {
    parent::setup();

    if( $this->parent && 'user' == $this->parent->get_subject() )
    {
      $this->set_variable( 'user_id', $this->parent->get_record()->id );
    }
    else
    {
      try
      {
        $this->user_list->process();
        $this->set_variable( 'user_list', $this->user_list->get_variables() );
      }
      catch( \cenozo\exception\permission $e ) {}
    }

    // set the view's items
    $this->set_item( 'site_id', lib::create( 'business\session' )->get_site()->id, true );
    $this->set_item( 'date', $this->date, true );
    $this->set_item( 'start_time', $this->start_time, true );
    $this->set_item( 'end_time', $this->end_time, true );
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
    $role_class_name = lib::get_class_name( 'database\role' );
    $db_role = $role_class_name::get_unique_record( 'name', 'operator' );
    if( is_null( $modifier ) ) $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'role_id', '=', $db_role->id );
    $modifier->where( 'site_id', '=', lib::create( 'business\session' )->get_site()->id );

    $user_class_name = lib::get_class_name( 'database\user' );
    return $user_class_name::count( $modifier );
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
    $role_class_name = lib::get_class_name( 'database\role' );
    $db_role = $role_class_name::get_unique_record( 'name', 'operator' );
    if( is_null( $modifier ) ) $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'role_id', '=', $db_role->id );
    $modifier->where( 'site_id', '=', lib::create( 'business\session' )->get_site()->id );

    $user_class_name = lib::get_class_name( 'database\user' );
    return $user_class_name::select( $modifier );
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
