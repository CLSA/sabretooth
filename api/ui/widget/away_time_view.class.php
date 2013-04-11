<?php
/**
 * away_time_view.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui\widget;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * widget away_time view
 */
class away_time_view extends \cenozo\ui\widget\base_view
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
    parent::__construct( 'away_time', 'view', $args );
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

    // create an associative array with everything we want to display about the away time
    $this->add_item( 'role_id', 'hidden' );
    $this->add_item( 'site_id', 'hidden' );
    $this->add_item( 'user_id', 'enum', 'User' );
    $this->add_item( 'start_datetime', 'datetimesec', 'Start' );
    $this->add_item( 'end_datetime', 'datetimesec', 'End' );
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

    $role_class_name = lib::get_class_name( 'database\role' );
    $user_class_name = lib::get_class_name( 'database\user' );

    $current_user_id = $this->get_record()->user_id;

    $users = array();
    $db_role = $role_class_name::get_unique_record( 'name', 'operator' );
    $user_mod = lib::create( 'database\modifier' );
    $user_mod->where( 'role_id', '=', $db_role->id );
    $user_mod->where( 'site_id', '=', lib::create( 'business\session' )->get_site()->id );
    $user_mod->order( 'name' );
    $found = false;
    foreach( $user_class_name::select( $user_mod ) as $db_user )
    {
      // need to check if the current user is in the list
      $users[$db_user->id] = $db_user->name;
      if( $db_user->id == $current_user_id ) $found = true;
    }

    // add the current user to the list if they weren't found
    if( !$found ) $users[$current_user_id] = $this->get_record()->get_user()->name;

    $this->set_item( 'role_id', $this->get_record()->role_id );
    $this->set_item( 'site_id', $this->get_record()->site_id );
    $this->set_item( 'user_id', $this->get_record()->user_id, true, $users );
    $this->set_item( 'start_datetime', $this->get_record()->start_datetime, true );
    $this->set_item( 'end_datetime', $this->get_record()->end_datetime, true );
  }
}
?>
