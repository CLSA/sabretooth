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

    // determine which site's calendar should be displayed
    $db_site = NULL;
    if( !is_null( $this->parent ) &&
        'appointment_view' == $this->parent->get_class_name() )
    {
      $db_site = $this->parent->get_record()->get_participant()->get_effective_site();
    }
    else if( !is_null( $this->parent ) &&
             'appointment_add' == $this->parent->get_class_name() &&
             !is_null( $this->parent->parent ) &&
             'participant_add_appointment' == $this->parent->parent->get_class_name() )
    {
      $db_site = $this->parent->parent->get_record()->get_effective_site();
    }
    else
    {
      $db_site = lib::create( 'business\session' )->get_site();
    }

    // display the site's calendar (or a warning if there is no site)
    if( is_null( $db_site ) )
    {
      $this->set_variable( 'site_id', 0 );
      $this->set_heading( 'WARNING! Participant does not belong to any site, showing blank calendar' );
    }
    else
    {
      $this->set_variable( 'site_id', $db_site->id );
      $this->set_heading( 'Open appointment slots for '.$db_site->name );
    }

    $this->set_variable( 'allow_all_day', false );
    $this->set_variable( 'editable', false );
  }
}
