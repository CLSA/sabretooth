<?php
/**
 * site_calendar.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui\widget;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * widget site calendar
 * 
 * @package sabretooth\ui
 */
class site_calendar extends \cenozo\ui\widget\base_calendar
{
  /**
   * Constructor
   * 
   * Defines all variables required by the site calendar.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'site', $args );
    $this->set_heading(
      'Open appointment slots for '.lib::create( 'business\session' )->get_site()->name );
  }
  
  /**
   * Set the rows array needed by the template.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function finish()
  {
    parent::finish();

    // get the referred participant's site and make id a variable
    if( !is_null( $this->parent ) &&
        'appointment_add' == $this->parent->get_class_name() &&
        !is_null( $this->parent->parent ) &&
        'participant_add_appointment' == $this->parent->parent->get_class_name() )
      $this->set_variable( 'site_id', $this->parent->parent->get_record()->get_primary_site()->id );

    $this->set_variable( 'allow_all_day', false );
    $this->set_variable( 'editable', false );
  }
}
?>
