<?php
/**
 * user_list.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui\widget;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * widget user list
 */
class user_list extends \cenozo\ui\widget\user_list
{
  /**
   * Overrides the parent class method to remove opal instances from the list
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the list.
   * @return int
   * @access protected
   */
  public function determine_record_count( $modifier = NULL )
  {
    $role_class_name = lib::get_class_name( 'database\role' );
    $db_role = $role_class_name::get_unique_record( 'name', 'opal' );
    if( is_null( $modifier ) ) $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'role_id', '!=', $db_role->id );
    return parent::determine_record_count( $modifier );
  }
  
  /**
   * Overrides the parent class method to remove opal instances from the list
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the list.
   * @return array( record )
   * @access protected
   */
  public function determine_record_list( $modifier = NULL )
  {
    $role_class_name = lib::get_class_name( 'database\role' );
    $db_role = $role_class_name::get_unique_record( 'name', 'opal' );
    if( is_null( $modifier ) ) $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'role_id', '!=', $db_role->id );
    return parent::determine_record_list( $modifier );
  }
}
?>
