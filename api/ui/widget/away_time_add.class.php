<?php
/**
 * away_time_add.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui\widget;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * widget away_time add
 */
class away_time_add extends \cenozo\ui\widget\base_view
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
    parent::__construct( 'away_time', 'add', $args );
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
    
    // define all columns defining this record
    $this->add_item( 'role_id', 'hidden' );
    $this->add_item( 'site_id', 'hidden' );
    $this->add_item( 'user_id', 'enum', 'User' );
    $this->add_item( 'start_datetime', 'datetime', 'Start' );
    $this->add_item( 'end_datetime', 'datetime', 'End' );
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

    $db_role = $role_class_name::get_unique_record( 'name', 'operator' );
    $db_site = lib::create( 'business\session' )->get_site();

    $users = array();
    $user_mod = lib::create( 'database\modifier' );
    $user_mod->where( 'role_id', '=', $db_role->id );
    $user_mod->where( 'site_id', '=', $db_site->id );
    foreach( $user_class_name::select( $user_mod ) as $db_user ) $users[$db_user->id] = $db_user->name;

    $this->set_item( 'role_id', $db_role->id );
    $this->set_item( 'site_id', $db_site->id );
    $this->set_item( 'user_id', key( $users ), true, $users );
    $this->set_item( 'start_datetime', '' );
    $this->set_item( 'end_datetime', '' );
  }
}
?>
