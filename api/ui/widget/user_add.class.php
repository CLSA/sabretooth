<?php
/**
 * user_add.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui\widget;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * widget user add
 */
class user_add extends \cenozo\ui\widget\user_add
{
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

    $session = lib::create( 'business\session' );
    $is_top_tier = 3 == $session->get_role()->tier;

    // create enum arrays
    $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'name', '!=', 'opal' );
    $modifier->where( 'tier', '<=', $session->get_role()->tier );
    $roles = array();
    foreach( $role_class_name::select( $modifier ) as $db_role )
      $roles[$db_role->id] = $db_role->name;
    
    // make operator the default new role
    $this->set_item( 'role_id', array_search( 'operator', $roles ), true, $roles );
  }
}
?>
