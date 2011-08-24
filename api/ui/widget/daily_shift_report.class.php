<?php
/**
 * daily_shift_report.class.php
 * 
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui\widget;
use sabretooth\log, sabretooth\util;
use sabretooth\business as bus;
use sabretooth\database as db;
use sabretooth\exception as exc;

/**
 * widget daily shift report
 * 
 * @package sabretooth\ui
 */
class daily_shift_report extends base_report
{
  /**
   * Constructor
   * 
   * Defines all variables which need to be set for the associated template.
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'daily_shift', $args );

    $this->restrict_by_site();
    
    $this->set_variable( 'description',
      'This report lists operator daily_shift.  The report can either be generated for a '.
      'particilar day (which will include start and end times), or overall.  The report '.
      'includes the number of completed interviews, total working time calls per hour and '.
      'average interview length.' );

    // add paramters to the report
    $this->add_parameter( 'date', 'date', 'Date', 'Leave blank for an overall report.' );

  }

  /**
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @access public
   */
  public function finish()
  {
    parent::finish();

    $this->set_parameter( 'date', '', false );
    
    $this->finish_setting_parameters();
  }
}
?>
