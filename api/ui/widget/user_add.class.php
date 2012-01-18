<?php
/**
 * user_add.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui\widget;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * widget user add
 * 
 * @package sabretooth\ui
 */
class user_add extends \cenozo\ui\widget\user_add
{
  /**
   * Finish setting the variables in a widget.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function finish()
  {
    parent::finish();
    
    $session = lib::create( 'business\session' );
    $is_top_tier = 3 == $session->get_role()->tier;

    // create enum arrays
    $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'tier', '<=', $session->get_role()->tier );
    $roles = array();
    $role_class_name = lib::get_class_name( 'database\role' );
    foreach( $role_class_name::select( $modifier ) as $db_role )
      $roles[$db_role->id] = $db_role->name;
    
    $sites = array();
    if( $is_top_tier )
    {
      $site_class_name = lib::get_class_name( 'database\site' );
      foreach( $site_class_name::select( $modifier ) as $db_site )
        $sites[$db_site->id] = $db_site->name;
    }

    // set the view's items
    $this->set_item( 'name', '', true );
    $this->set_item( 'first_name', '', true );
    $this->set_item( 'last_name', '', true );
    $this->set_item( 'active', true, true );
    $value = $is_top_tier ? current( $sites ) : $session->get_site()->id;
    $this->set_item( 'site_id', $value, true, $is_top_tier ? $sites : NULL );
    $this->set_item( 'role_id', array_search( 'operator', $roles ), true, $roles );

    $this->finish_setting_items();
  }
}
?>
