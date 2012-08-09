<?php
/**
 * user_view.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui\widget;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * widget user view
 */
class user_view extends \cenozo\ui\widget\user_view
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
    
    $session = lib::create( 'business\session' );
    $role_class_name = lib::get_class_name( 'database\role' );
    $operation_class_name = lib::get_class_name( 'database\operation' );

    $is_operator = $this->get_record()->has_access(
                   $session->get_site(),
                   $role_class_name::get_unique_record( 'name', 'operator' ) );

    // only show shift calendar if the current user's role is greater than the base tier and
    // the viewed user is an operator at this site
    $view_shifts = $is_operator && 1 < $session->get_role()->tier;
    $this->set_variable( 'view_shifts', $view_shifts );
    if( $view_shifts )
    {
      $db_operation = $operation_class_name::get_operation( 'widget', 'shift', 'calendar' );
      if( $session->is_allowed( $db_operation ) )
        $this->add_action( 'calendar', 'Shift Calendar', NULL,
          'View the operator\'s shift calendar.' );
    }
  }
}
?>
