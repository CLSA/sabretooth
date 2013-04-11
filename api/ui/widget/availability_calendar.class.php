<?php
/**
 * availability_calendar.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui\widget;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * widget availability calendar
 */
class availability_calendar extends \cenozo\ui\widget\base_calendar
{
  /**
   * Constructor
   * 
   * Defines all variables required by the availability calendar.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'availability', $args );
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

    $this->set_heading( 'Availabilities for '.lib::create( 'business\session' )->get_site()->name );
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

    $this->set_variable( 'allow_all_day', false );
    $this->set_variable( 'editable', false );
    $this->set_variable( 'default_view', 'basicWeek' );
    $this->set_variable( 'controls', false );
  }
}
?>
