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
    $this->add_item( 'site_id', 'enum', 'Site' );
    $this->add_item( 'date', 'date', 'Date' );
    $this->add_item( 'start_time', 'time', 'Start Time' );
    $this->add_item( 'end_time', 'time', 'End Time' );

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
    
    // create enum arrays
    $sites = array();
    foreach( \sabretooth\database\site::select() as $db_site )
      $sites[$db_site->id] = $db_site->name;

    if( $this->parent )
    {
      if( 'user' == $this->parent->get_subject() )
      {
        $this->set_variable( 'user_id', $this->parent->get_record()->id );
        $this->set_item( 'site_id', \sabretooth\session::self()->get_site()->id, true, $sites );
      }
      else if( 'site' == $this->parent->get_subject() )
      {
        // replace the site enum with a hidden variable
        $this->add_item( 'site_id', 'hidden' );
        $this->set_item( 'site_id', $this->parent->get_record()->id );
        $this->user_list->finish();
        $this->set_variable( 'user_list', $this->user_list->get_variables() );
      }
      else
      {
        throw new \sabretooth\exception\runtime(
          'Shift widget has an invalid parent "'.$this->parent->get_subject().
          '", which should be "user" or "site".', __METHOD__ );
        
      }
    }
    else
    {
      $this->user_list->finish();
      $this->set_variable( 'user_list', $this->user_list->get_variables() );
      $this->set_item( 'site_id', \sabretooth\session::self()->get_site()->id, true, $sites );
    }

    // set the view's items
    $this->set_item( 'date', '', true );
    $this->set_item( 'start_time', '', true );
    $this->set_item( 'end_time', '', true );
    
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
   * The user list widget used to define the access type.
   * @var user_list
   * @access protected
   */
  protected $user_list = NULL;
}
?>
