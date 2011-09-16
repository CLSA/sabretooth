<?php
/**
 * system_message_add.class.php
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
 * widget system_message add
 * 
 * @package sabretooth\ui
 */
class system_message_add extends base_view
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
    parent::__construct( 'system_message', 'add', $args );
    
    // define all columns defining this record

    $type = 'administrator' == bus\session::self()->get_role()->name ? 'enum' : 'hidden';
    $this->add_item( 'site_id', $type, 'Site',
      'Leaving the site blank will show the message across all sites.' );
    $this->add_item( 'role_id', 'enum', 'Role',
      'Leaving the role blank will show the message to all roles.' );
    $this->add_item( 'title', 'string', 'Title' );
    $this->add_item( 'note', 'text', 'Note' );
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
    $is_administrator = 'administrator' == $session->get_role()->name;
    
    // create enum arrays
    if( $is_administrator )
    {
      $sites = array();
      foreach( db\site::select() as $db_site ) $sites[$db_site->id] = $db_site->name;
    }

    $roles = array();
    $modifier = new db\modifier();
    if( !$is_administrator ) $modifier->where( 'name', '!=', 'administrator' );
    foreach( db\role::select( $modifier ) as $db_role ) $roles[$db_role->id] = $db_role->name;

    // set the view's items
    $this->set_item(
      'site_id', $session->get_site()->id, false, $is_administrator ? $sites : NULL );
    $this->set_item( 'role_id', null, false, $roles );
    $this->set_item( 'title', null, true );
    $this->set_item( 'note', null, true );

    $this->finish_setting_items();
  }
}
?>
