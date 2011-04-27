<?php
/**
 * base_appointment_view.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui;
use sabretooth\log, sabretooth\util;
use sabretooth\business as bus;
use sabretooth\database as db;
use sabretooth\exception as exc;

/**
 * base class for appointment view/add classes
 * 
 * @package sabretooth\ui
 */
abstract class base_appointment_view extends base_view
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
    
    try
    {
      // create the site calendar widget
      $this->site_calendar = new site_calendar( $args );
      $this->site_calendar->set_parent( $this );
    }
    catch( exc\permission $e )
    {
      $this->site_calendar = NULL;
    }
  }

  /**
   * Finish setting the variables in a widget.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function finish()
  {
    parent::finish();
    
    // set up the site calendar if editing is enabled
    if( $this->editable )
    {
      if( !is_null( $this->site_calendar ) )
      {
        $this->site_calendar->finish();
        $this->set_variable( 'site_calendar', $this->site_calendar->get_variables() );
      }
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
