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
class user_view extends \cenozo\ui\widget\user_view
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
    $role_class_name = lib::get_class_name( 'database\role' );
    $is_operator = $this->get_record()->has_access(
                   $session->get_site(),
                   $role_class_name::get_unique_record( 'name', 'operator' ) );

    // only show shift calendar if the current user's role is greater than the base tier and
    // the viewed user is an operator at this site
    $view_shifts = $is_operator && 1 < $session->get_role()->tier;
    $this->set_variable( 'view_shifts', $view_shifts );
    if( $view_shifts )
      $this->add_action( 'calendar', 'Shift Calendar', NULL,
        'View the operator\'s shift calendar.' );

    $this->finish_setting_items();
  }
}
?>
