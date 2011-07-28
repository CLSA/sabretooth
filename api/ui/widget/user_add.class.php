<?php
/**
 * user_add.class.php
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
 * widget user add
 * 
 * @package sabretooth\ui
 */
class user_add extends base_view
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
    parent::__construct( 'user', 'add', $args );
    
    // define all columns defining this record
    $this->add_item( 'name', 'string', 'Username' );
    $this->add_item( 'first_name', 'string', 'First name' );
    $this->add_item( 'last_name', 'string', 'Last name' );
    $this->add_item( 'active', 'boolean', 'Active' );

    $type = 'administrator' == bus\session::self()->get_role()->name
          ? 'enum'
          : 'hidden';
    $this->add_item( 'site_id', $type, 'Site' );
    $this->add_item( 'role_id', 'enum', 'Role' );
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
    $modifier = new db\modifier();
    if( !$is_administrator ) $modifier->where( 'name', '!=', 'administrator' );
    $roles = array();
    foreach( db\role::select( $modifier ) as $db_role )
      $roles[$db_role->id] = $db_role->name;
    
    $sites = array();
    if( $is_administrator )
    {
      foreach( db\site::select( $modifier ) as $db_site )
        $sites[$db_site->id] = $db_site->name;
    }

    // set the view's items
    $this->set_item( 'name', '', true );
    $this->set_item( 'first_name', '', true );
    $this->set_item( 'last_name', '', true );
    $this->set_item( 'active', true, true );
    $value = $is_administrator ? current( $sites ) : $session->get_site()->id;
    $this->set_item( 'site_id', $value, true, $is_administrator ? $sites : NULL );
    $this->set_item( 'role_id', array_search( 'operator', $roles ), true, $roles );

    $this->finish_setting_items();
  }
}
?>
