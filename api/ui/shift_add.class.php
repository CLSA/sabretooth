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
    $this->add_item( 'site_id', 'hidden' );
    $this->add_item( 'event', 'enum', 'Event' );
    $this->add_item( 'date', 'date', 'Date' );
    $this->add_item( array( 'start_time', 'end_time' ), 'time', 'Time' );
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
    
    // this widget must have a parent, and it's subject must be a user or site

    if( is_null( $this->parent ) ||
        ( 'user' != $this->parent->get_subject() && 'site' != $this->parent->get_subject() ) )
      throw new \sabretooth\exception\runtime(
        'Consent widget must have a parent with either user or site as the subject.', __METHOD );

    // get the parent's subject
    $parent_subject = $this->parent->get_subject();
    
    $site_id = 0;
    if( 'user' == $parent_subject )
    {
      $site_id = \sabretooth\session::get_site()->id;
      $this->add_item( 'user_id', 'hidden' );
      
      // set the user id
      $this->set_item( 'user_id', $this->parent->get_record()->id ); 
    }
    else // 'site' == $parent_subject
    {
      $site_id = $this->parent->get_record()->id;
      $this->add_item( 'user_id', 'enum', 'User' );

      // create enum arrays
      $users = array();
      $modifier = new \sabretooth\database\modifier();
      $modifier->where( 'site_id', '=', $this->parent->get_record()->id );
      foreach( \sabretooth\database\user::select( $modifier ) as $db_user )
        $users[$db_user->id] = $db_user->name;
      
      // set the user id
      $this->set_item( 'user_id', '', true );
    }
    
    

    // set the view's items
    $this->set_item( 'site_id', $site_id );
    $this->set_item( 'date', '', true );
    $this->set_item( 'start_time', '', true );
    $this->set_item( 'end_time', '', true );

    $this->finish_setting_items();
  }
}
?>
