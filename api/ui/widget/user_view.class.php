<?php
/**
 * user_view.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui\widget;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * widget user view
 * 
 * @package sabretooth\ui
 */
class user_view extends \cenozo\ui\widget\base_view
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
    
    $role_class_name = lib::get_class_name( 'database\role' );
    $is_operator = $this->get_record()->has_access(
                     lib::create( 'business\session' )->get_site(),
                     $role_class_name::get_unique_record( 'name', 'operator' ) );

    // only show shift calendar if the current user's role is greater than the base tier and
    // the viewed user is an operator at this site
    $this->set_variable( 'view_shifts',
      $is_operator && 1 < lib::create( 'business\session' )->get_role()->tier );
  }
}
?>
