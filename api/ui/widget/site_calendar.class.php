<?php
/**
 * site_calendar.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui\widget;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * widget site calendar
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
  }

  /**
   * Processes arguments, preparing them for the operation.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @throws exception\notice
   * @access protected
   */
  protected function prepare()
  {
    parent::prepare();

    $this->set_heading(
      'Open appointment slots for '.lib::create( 'business\session' )->get_site()->name );
  }
  
  /**
   * Set the rows array needed by the template.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access protected
   */
  protected function setup()
  {
    parent::setup();

    // get the referred participant's site and make id a variable
    if( !is_null( $this->parent ) &&
        'appointment_add' == $this->parent->get_class_name() &&
        !is_null( $this->parent->parent ) &&
        'participant_add_appointment' == $this->parent->parent->get_class_name() )
    {
      $db_site = lib::create( 'database\site',
        $this->parent->parent->get_record()->get_primary_site()->id );
      $this->set_variable( 'site_id', $db_site->id );
      $this->set_heading( 'Open appointment slots for '.$db_site->name );
    }
    else $this->set_variable( 'site_id', lib::create( 'business\session' )->get_site()->id );

    $this->set_variable( 'allow_all_day', false );
    $this->set_variable( 'editable', false );
  }
}
?>
