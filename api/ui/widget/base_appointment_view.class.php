<?php
/**
 * base_appointment_view.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui\widget;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * base class for appointment view/add classes
 */
abstract class base_appointment_view extends \cenozo\ui\widget\base_view
{
  /**
   * Constructor
   * 
   * Defines all variables which need to be set for the associated template.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $name The name of the operation.
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $name, $args )
  {
    parent::__construct( 'appointment', $name, $args );
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
    
    // create the site calendar widget
    $this->site_calendar = lib::create( 'ui\widget\site_calendar', $this->arguments );
    $this->site_calendar->set_parent( $this );
    $this->site_calendar->set_variable( 'default_view', 'basicWeek' );
  }

  /**
   * Finish setting the variables in a widget.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access protected
   */
  protected function setup()
  {
    parent::setup();
    
    // set up the site calendar if editing is enabled
    if( $this->get_editable() || 'add' == $this->get_name() )
    {
      try
      {
        $this->site_calendar->process();
        $this->set_variable( 'site_calendar', $this->site_calendar->get_variables() );
      }
      catch( \cenozo\exception\permission $e ) {}
    }
  }

  /**
   * Site calendar used to help find appointment availability
   * @var site_calendar $site_calendar
   * @access protected
   */
  protected $site_calendar = NULL;
}
?>
