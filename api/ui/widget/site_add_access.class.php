<?php
/**
 * site_add_access.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui\widget;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * widget site add_access
 */
class site_add_access extends \cenozo\ui\widget\site_add_access
{
  /**
   * Overrides the role list widget's method.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the list.
   * @return int
   * @access protected
   */
  public function determine_role_count( $modifier = NULL )
  {
    if( is_null( $modifier ) ) $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'name', '!=', 'opal' );
    return parent::determine_role_count( $modifier );
  }

  /**
   * Overrides the role list widget's method.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the list.
   * @return array( record )
   * @access protected
   */
  public function determine_role_list( $modifier = NULL )
  {
    if( is_null( $modifier ) ) $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'name', '!=', 'opal' );
    return parent::determine_role_list( $modifier );
  }
}
?>
