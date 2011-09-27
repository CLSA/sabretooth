<?php
/**
 * self_home.class.php
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
 * widget self home
 * 
 * @package sabretooth\ui
 */
class self_home extends \sabretooth\ui\widget
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
    parent::__construct( 'self', 'home', $args );
    $this->show_heading( false );
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

    $session = bus\session::self();
    $db_user = $session->get_user();
    $db_role = $session->get_role();
    $db_site = $session->get_site();

    // determine the user's last activity
    $db_activity = $session->get_user()->get_last_activity();

    $this->set_variable( 'version',
      bus\setting_manager::self()->get_setting( 'general', 'version' ) );
    $this->set_variable( 'user_name', $db_user->first_name.' '.$db_user->last_name );
    $this->set_variable( 'role_name', $db_role->name );
    $this->set_variable( 'site_name', $db_site->name );
    if( $db_activity )
    {
      $this->set_variable( 'last_day', util::get_formatted_date( $db_activity->datetime ) );
      $this->set_variable( 'last_time', util::get_formatted_time( $db_activity->datetime ) );
    }

    // add any messages that apply to this user
    $message_list = array();

    // global messages go first
    $modifier = new db\modifier();
    $modifier->where( 'site_id', '=', NULL );
    $modifier->where( 'role_id', '=', NULL );
    foreach( db\system_message::select( $modifier ) as $db_system_message )
    {
      $message_list[] = array( 'title' => $db_system_message->title,
                               'note' => $db_system_message->note );
    }
    
    // then all-site messages
    $modifier = new db\modifier();
    $modifier->where( 'site_id', '=', NULL );
    $modifier->where( 'role_id', '=', $db_role->id );
    foreach( db\system_message::select( $modifier ) as $db_system_message )
    {
      $message_list[] = array( 'title' => $db_system_message->title,
                               'note' => $db_system_message->note );
    }

    // then all-role site-specific messages
    $modifier = new db\modifier();
    $modifier->where( 'site_id', '=', $db_site->id );
    $modifier->where( 'role_id', '=', NULL );
    foreach( db\system_message::select( $modifier ) as $db_system_message )
    {
      $message_list[] = array( 'title' => $db_system_message->title,
                               'note' => $db_system_message->note );
    }

    // then role-specific site-specific messages
    $modifier = new db\modifier();
    $modifier->where( 'site_id', '=', $db_site->id );
    $modifier->where( 'role_id', '=', $db_role->id );
    foreach( db\system_message::select( $modifier ) as $db_system_message )
    {
      $message_list[] = array( 'title' => $db_system_message->title,
                               'note' => $db_system_message->note );
    }

    $this->set_variable( 'message_list', $message_list );

  }
}
?>
